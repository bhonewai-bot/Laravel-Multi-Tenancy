<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\TenantDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class DomainController extends Controller
{
    public function __construct(
        private TenantDomainService $domainService
    ) {}

    public function index(): View
    {
        $tenant = tenant();

        $domains = Domain::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('domain')
            ->get();

        return view('tenant.domains.index', [
            'domains' => $domains,
            'tenant' => $tenant,
            'domainService' => $this->domainService,
        ]);
    }

    public function create(): View
    {
        return view('tenant.domains.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = tenant();

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));

        $validator = Validator::make($request->all(), [
            'domain' => ['required', 'string', 'max:255', "unique:{$centralConnection}.domains,domain"],
        ]);

        $validator->after(function ($validator) use ($tenant, $request) {
            $domain = $this->domainService->normalize($request->input('domain', ''));

            if (! $this->isValidDomain($domain)) {
                $validator->errors()->add('domain', 'Enter a valid host (no http://, no path).');
            }

            if ($this->domainService->isCentralDomain($domain)) {
                $validator->errors()->add('domain', 'Central domains cannot be used as custom tenant domains.');
            }

            if ($this->domainService->isPrimarySubDomain($tenant, $domain)) {
                $validator->errors()->add('domain', 'Primary tenant domain is already in use.');
            }
        });

        $data = $validator->validate();
        $domain = $this->domainService->normalize($data['domain']);

        Domain::query()->create([
            'tenant_id' => $tenant->id,
            'domain' => $domain,
            'verification_code' => $this->domainService->makeVerificationCode(),
            'verified_at' => null,
        ]);

        return redirect()
            ->route('tenant.domains.index')
            ->with('success', "Domain {$domain} added successfully. Add DNS TXT record and verify.");
    }

    public function verify(Domain $domain): RedirectResponse
    {
        $tenant = tenant();

        if ($domain->tenant_id !== $tenant->id) {
            abort(404);
        }

        if ($this->domainService->isPrimarySubDomain($tenant, $domain->domain)) {
            return back()->with('success', 'Primary tenant subdomain is trusted automatically.');
        }

        if (! $domain->verification_code) {
            $domain->verification_code = $this->domainService->makeVerificationCode();
            $domain->verified_at = null;
            $domain->save();
        }

        $ok = $this->domainService->checkDnsTxtVerification($domain->domain, $domain->verification_code);

        if (! $ok) {
            $record = $this->domainService->verificationRecordName($domain->domain);

            return back()->with('error', "DNS TXT not matched. Expected: {$record} = {$domain->verification_code}");
        }

        $domain->verified_at = now();
        $domain->save();

        return back()->with('success', "Domain {$domain->domain} verified successfully.");
    }

    public function destroy(Domain $domain): RedirectResponse
    {
        $tenant = tenant();

        if ($domain->tenant_id !== $tenant->id) {
            abort(404);
        }

        if ($this->domainService->isPrimarySubDomain($tenant, $domain->domain)) {
            return back()->with('error', 'Primary tenant subdomain cannot be deleted.');
        }

        $domain->delete();

        return redirect()
            ->route('tenant.domains.index')
            ->with('success', 'Custom domain deleted successfully.');
    }

    private function isValidDomain(string $domain): bool
    {
        if ($domain == '') {
            return false;
        }

        if (str_contains($domain, '://') || str_contains($domain, '/')) {
            return false;
        }

        return filter_var('http://' . $domain, FILTER_VALIDATE_URL) !== false;
    }
}
