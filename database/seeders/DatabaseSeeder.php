<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Book;
use App\Models\Location;
use App\Models\VolumeDiscount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CREAR USUARIOS DE PRUEBA
        User::create([
            'name' => 'Sergio',
            'last_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin1234'),
            'role' => 'admin',
            'active' => true
        ]);

        User::create([
            'name' => 'Juan',
            'last_name' => 'Bodeguero',
            'email' => 'empleado@gmail.com',
            'password' => Hash::make('alan1234'),
            'role' => 'warehouseman',
            'active' => true
        ]);

        User::create([
            'name' => 'Colegio San José',
            'last_name' => 'Institución',
            'email' => 'miguel@gmail.com',
            'password' => Hash::make('user1234'),
            'role' => 'user',
            'customer_type' => 'institutional',
            'tax_id' => '900123456-1',
            'active' => true
        ]);
        User::create([
            'name' => 'Alan',
            'last_name' => 'Olarte Vazquez',
            'email' => 'dummy@gmail.com',
            'password' => Hash::make('alan1234'),
            'role' => 'user',
            'customer_type' => 'individual',
            'active' => true
        ]);

        // 2. CREAR UBICACIONES (ESTANTES) CON CAPACIDAD
        $locations = [
            ['code' => 'ESTANTE-A1', 'max_capacity' => 500],
            ['code' => 'ESTANTE-A2', 'max_capacity' => 500],
            ['code' => 'ESTANTE-B1', 'max_capacity' => 1000], // Rack grande
            ['code' => 'PALLET-01', 'max_capacity' => 2000],
        ];

        foreach ($locations as $loc) {
            Location::create($loc);
        }

        // 3. CREAR LIBROS DE INGLÉS (EFL)
        $books = [
            [
                'title' => 'English File Beginner Student Book',
                'isbn' => '9780194031165',
                'level' => 'A1',
                'cost' => 25.00,
                'price_unit' => 45.00,
                'units_per_package' => 10,
                'price_package' => 400.00,
                'autor' => 'Christina Latham-Koenig',
                'pages' => 160, 'year' => 2019, 'edition' => 4,
                'format' => 'Tapa Blanda', 'size' => 'A4', 'supplier' => 'Oxford University Press',
                'active' => true
            ],
            [
                'title' => 'Cambridge English First (FCE) Result',
                'isbn' => '9780194511926',
                'level' => 'B2',
                'cost' => 30.00,
                'price_unit' => 55.00,
                'units_per_package' => 5,
                'price_package' => 250.00,
                'autor' => 'Paul A. Davies',
                'pages' => 180, 'year' => 2021, 'edition' => 2,
                'format' => 'Tapa Dura', 'size' => 'A4', 'supplier' => 'Cambridge Press',
                'active' => true
            ],
            [
                'title' => 'Advanced Grammar in Use',
                'isbn' => '9781108924689',
                'level' => 'C1',
                'cost' => 35.00,
                'price_unit' => 65.00,
                'units_per_package' => 1,
                'price_package' => 65.00,
                'autor' => 'Martin Hewings',
                'pages' => 304, 'year' => 2023, 'edition' => 4,
                'format' => 'Tapa Blanda', 'size' => '240 x 170 mm', 'supplier' => 'Cambridge Press',
                'active' => true
            ]
        ];

        foreach ($books as $bookData) {
            Book::create($bookData);
        }

        // 4. CREAR REGLAS DE DESCUENTO POR VOLUMEN
        VolumeDiscount::create([
            'min_quantity' => 20,
            'max_quantity' => 50,
            'discount_percentage' => 10.00,
            'is_institutional' => true
        ]);

        VolumeDiscount::create([
            'min_quantity' => 51,
            'max_quantity' => null,
            'discount_percentage' => 25.00,
            'is_institutional' => true
        ]);
    }
}
