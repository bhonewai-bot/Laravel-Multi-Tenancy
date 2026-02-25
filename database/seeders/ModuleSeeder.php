<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Module::updateOrCreate(
            ['slug' => 'product'],
            [
                'name' => 'Product',
                'version' => '1.0.0',
                'description' => 'Product Module',
                'icon_path' => null,
                'price' => 0,
                'is_active' => true
            ]
        );
    }
}
