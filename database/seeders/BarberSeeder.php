<?php

namespace Database\Seeders;

use App\Models\Barber;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BarberSeeder extends Seeder
{
    public function run(): void
    {
        // الحلاق الأول - محمد
        $barber1 = Barber::create([
            'name' => 'محمد أحمد',
            'shop_name' => 'صالون محمد للحلاقة',
            'phone' => '+966501234567',
            'whatsapp' => '+966501234567',
            'email' => 'barber1@dawrak.com',
            'password' => Hash::make('barber123'),
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addYear(),
            'is_open' => true,
            'avg_service_minutes' => 20,
            'address' => 'حي النزهة، الرياض',
        ]);

        // خدمات الحلاق الأول
        $this->createServices($barber1->id, [
            ['category' => 'hair', 'name' => 'قصة شعر', 'price' => 30, 'duration_minutes' => 20],
            ['category' => 'hair', 'name' => 'قصة + غسيل', 'price' => 40, 'duration_minutes' => 30],
            ['category' => 'hair', 'name' => 'صبغة شعر', 'price' => 80, 'duration_minutes' => 60],
            ['category' => 'beard', 'name' => 'تشكيل لحية', 'price' => 20, 'duration_minutes' => 15],
            ['category' => 'beard', 'name' => 'حلاقة ذقن كاملة', 'price' => 15, 'duration_minutes' => 10],
            ['category' => 'special', 'name' => 'علاج فروة الرأس', 'price' => 60, 'duration_minutes' => 45],
            ['category' => 'offer', 'name' => 'عرض الجمعة (قصة + لحية)', 'price' => 40, 'duration_minutes' => 30],
        ]);

        // الحلاق الثاني - عبدالله
        $barber2 = Barber::create([
            'name' => 'عبدالله علي',
            'shop_name' => 'صالون النخبة',
            'phone' => '+966507654321',
            'whatsapp' => '+966507654321',
            'email' => 'barber2@dawrak.com',
            'password' => Hash::make('barber123'),
            'subscription_status' => 'trial',
            'is_open' => false,
            'avg_service_minutes' => 25,
            'address' => 'حي العليا، الرياض',
        ]);

        // خدمات الحلاق الثاني
        $this->createServices($barber2->id, [
            ['category' => 'hair', 'name' => 'قصة كلاسيكية', 'price' => 35, 'duration_minutes' => 25],
            ['category' => 'hair', 'name' => 'قصة فيد', 'price' => 45, 'duration_minutes' => 35],
            ['category' => 'beard', 'name' => 'تهذيب لحية', 'price' => 25, 'duration_minutes' => 20],
            ['category' => 'special', 'name' => 'كيراتين', 'price' => 150, 'duration_minutes' => 90],
            ['category' => 'special', 'name' => 'تونك للشعر', 'price' => 50, 'duration_minutes' => 40],
            ['category' => 'offer', 'name' => 'باقة VIP (قصة + لحية + غسيل)', 'price' => 70, 'duration_minutes' => 60],
        ]);
    }

    private function createServices(int $barberId, array $services): void
    {
        foreach ($services as $index => $service) {
            Service::create([
                'barber_id' => $barberId,
                'category' => $service['category'],
                'name' => $service['name'],
                'price' => $service['price'],
                'duration_minutes' => $service['duration_minutes'],
                'is_available' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
