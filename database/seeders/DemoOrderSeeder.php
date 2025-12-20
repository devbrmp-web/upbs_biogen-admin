<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SeedLot;
use App\Models\Shipment;
use App\Models\Variety;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have data to work with
        $sellableLots = SeedLot::sellable()->inStock()->inRandomOrder()->get();
        $varieties = Variety::inRandomOrder()->get();

        // Idempotency: if demo orders already exist (>=35), skip
        if (\App\Models\Order::count() >= 35) {
            $this->command->info('DemoOrderSeeder: Skipped (>=35 orders already present).');
            return;
        }

        if ($sellableLots->isEmpty() || $varieties->isEmpty()) {
            $this->command->warn('No sellable seed lots or varieties found. Run DemoDataSeeder and SeedLotSeeder first.');
            return;
        }

        // More realistic customer data patterns
        $customerTypes = [
            'individual_farmer' => [
                'names' => ['Budi Santoso', 'Siti Rahayu', 'Ahmad Wijaya', 'Dewi Sartika', 'Joko Susilo'],
                'addresses' => [
                    'Jl. Raya Desa Sukamaju No. 15, Kec. Cianjur, Jawa Barat',
                    'Dusun Krajan RT 02/RW 01, Desa Sumberejo, Kec. Batu, Jawa Timur',
                    'Jl. Tani Makmur No. 8, Desa Sidodadi, Kec. Lampung Tengah',
                    'Kampung Mekar Sari RT 03/RW 02, Desa Cibodas, Kec. Lembang, Jawa Barat',
                ],
                'phone_patterns' => ['0812', '0813', '0821', '0822', '0856'],
            ],
            'cooperative' => [
                'names' => ['Koperasi Tani Sejahtera', 'Gapoktan Maju Bersama', 'Koperasi Sumber Rezeki'],
                'addresses' => [
                    'Jl. Koperasi No. 12, Kec. Sukabumi, Jawa Barat',
                    'Jl. Gotong Royong No. 25, Kec. Malang, Jawa Timur',
                ],
                'phone_patterns' => ['0251', '0341', '0274'],
            ],
            'institution' => [
                'names' => ['Dinas Pertanian Kab. Bogor', 'Balai Penyuluhan Pertanian Cianjur'],
                'addresses' => [
                    'Jl. Pemda No. 1, Kota Bogor, Jawa Barat',
                    'Jl. Raya Cianjur-Bandung Km 5, Cianjur, Jawa Barat',
                ],
                'phone_patterns' => ['0251', '0263'],
            ],
        ];

        // Enhanced order patterns with realistic distribution
        $ordersMatrix = [
            // More awaiting payment (realistic conversion rate)
            ['status' => Order::STATUS_AWAITING_PAYMENT, 'method' => Order::SHIPPING_PICKUP, 'weight' => 3],
            ['status' => Order::STATUS_AWAITING_PAYMENT, 'method' => Order::SHIPPING_DELIVERY, 'weight' => 4],
            
            // Paid orders
            ['status' => Order::STATUS_PAID, 'method' => Order::SHIPPING_PICKUP, 'weight' => 2],
            ['status' => Order::STATUS_PAID, 'method' => Order::SHIPPING_DELIVERY, 'weight' => 3],
            
            // Processing orders
            ['status' => Order::STATUS_PROCESSING, 'method' => Order::SHIPPING_PICKUP, 'weight' => 2],
            ['status' => Order::STATUS_PROCESSING, 'method' => Order::SHIPPING_DELIVERY, 'weight' => 3],
            
            // Ready for pickup (pickup only)
            ['status' => Order::STATUS_PICKUP_READY, 'method' => Order::SHIPPING_PICKUP, 'weight' => 2],
            
            // Completed orders (simplified - no more shipped/picked_up intermediate states)
            ['status' => Order::STATUS_COMPLETED, 'method' => Order::SHIPPING_PICKUP, 'weight' => 3],
            ['status' => Order::STATUS_COMPLETED, 'method' => Order::SHIPPING_DELIVERY, 'weight' => 3],
            
            // Some cancelled orders (realistic)
            ['status' => Order::STATUS_CANCELLED, 'method' => Order::SHIPPING_PICKUP, 'weight' => 1],
            ['status' => Order::STATUS_CANCELLED, 'method' => Order::SHIPPING_DELIVERY, 'weight' => 1],
        ];
        
        $createdCount = 0;
        // Create 35 orders with more realistic distribution
        while ($createdCount < 35) {
            // Select order pattern based on weights
            $weightedMatrix = [];
            foreach ($ordersMatrix as $pattern) {
                for ($i = 0; $i < $pattern['weight']; $i++) {
                    $weightedMatrix[] = $pattern;
                }
            }
            $definition = $weightedMatrix[array_rand($weightedMatrix)];
            
            // Select customer type and data
            $customerType = fake()->randomElement(array_keys($customerTypes));
            $customerData = $customerTypes[$customerType];
            
            // Generate realistic timestamps (orders from last 3 months)
            $createdAt = fake()->dateTimeBetween('-3 months', 'now');
            
            // Realistic shipping costs based on region
            $shippingCost = $definition['method'] === Order::SHIPPING_PICKUP ? 0 : 
                fake()->randomElement([15000, 20000, 25000, 30000, 35000, 45000, 55000]);
            
            $order = Order::create([
                'order_code' => 'WUB-' . $createdAt->format('Ymd') . '-' . strtoupper(Str::random(5)),
                'customer_name' => fake()->randomElement($customerData['names']),
                'customer_phone' => fake()->randomElement($customerData['phone_patterns']) . fake()->numerify('########'),
                'customer_email' => $customerType === 'individual_farmer' ? 
                    fake()->optional(0.6)->safeEmail() : // Farmers less likely to have email
                    fake()->optional(0.9)->safeEmail(),   // Institutions more likely
                'customer_address' => fake()->randomElement($customerData['addresses']),
                'shipping_method' => $definition['method'],
                'status' => $definition['status'],
                'shipping_cost' => $shippingCost,
                'courier_name' => $definition['method'] === Order::SHIPPING_DELIVERY ? 
                    fake()->randomElement([Shipment::COURIER_POS_INDONESIA, Shipment::COURIER_INDAH_CARGO]) : null,
                'courier_service' => $definition['method'] === Order::SHIPPING_DELIVERY ? 
                    fake()->randomElement(['REG', 'EXPRESS']) : null,
                'notes' => [],
                'subtotal' => 0,
                'total_amount' => $shippingCost,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Create realistic order items based on customer type
            $itemsCount = match ($customerType) {
                'individual_farmer' => rand(1, 3),    // Small farmers buy less variety
                'cooperative' => rand(2, 5),          // Cooperatives buy more variety
                'institution' => rand(3, 6),          // Institutions buy most variety
            };
            
            $subtotal = 0;
            $usedVarieties = []; // Prevent duplicate varieties in same order
            
            for ($i = 0; $i < $itemsCount; $i++) {
                $seedLot = $sellableLots->shuffle()->first();
                $variety = $seedLot->variety ?? $varieties->shuffle()->first();
                
                // Skip if variety already in this order
                if (in_array($variety->id, $usedVarieties)) {
                    continue;
                }
                $usedVarieties[] = $variety->id;
                
                $available = max(0, (int) $seedLot->quantity);
                if ($available <= 0) {
                    continue;
                }
                
                // Realistic quantities based on customer type and seed class
                $qty = match ($customerType) {
                    'individual_farmer' => match ($seedLot->seedClass->code ?? 'BS') {
                        'BS', 'FS' => rand(1, 10),      // 1-10 kg for basic/foundation
                        'PL' => rand(50, 200),          // 50-200 bottles for planlets
                        default => rand(1, 5),
                    },
                    'cooperative' => match ($seedLot->seedClass->code ?? 'BS') {
                        'BS', 'FS' => rand(10, 50),     // 10-50 kg for cooperatives
                        'PL' => rand(200, 500),         // 200-500 bottles
                        default => rand(5, 25),
                    },
                    'institution' => match ($seedLot->seedClass->code ?? 'BS') {
                        'BS', 'FS' => rand(25, 100),    // 25-100 kg for institutions
                        'PL' => rand(300, 1000),        // 300-1000 bottles
                        default => rand(10, 50),
                    },
                };
                
                // Ensure not exceeding available stock
                $qty = min($available, $qty);
                if ($qty <= 0) continue;

                $item = OrderItem::createFromVariety($order, $variety, $qty, $seedLot);
                $subtotal += (float) $item->total_price;

                // Reserve stock by decrementing lot quantity
                $seedLot->decrement('quantity', $qty);
            }

            // Update financial totals
            $order->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal + (float) $order->shipping_cost,
            ]);

            // Create payment records based on status with realistic timing
            $paymentMethod = fake()->randomElement([
                Payment::METHOD_VA_BRI,
                Payment::METHOD_VA_BNI,
                Payment::METHOD_QRIS,
                Payment::METHOD_BANK_TRANSFER,
            ]);

            $paymentStatus = match ($order->status) {
                Order::STATUS_AWAITING_PAYMENT => Payment::STATUS_PENDING,
                Order::STATUS_CANCELLED => Payment::STATUS_CANCELLED,
                default => Payment::STATUS_PAID,
            };

            // Realistic payment timing based on customer type
            $paymentDelay = match ($customerType) {
                'individual_farmer' => rand(30, 480),    // 30 min - 8 hours (slower payment)
                'cooperative' => rand(15, 240),          // 15 min - 4 hours (moderate)
                'institution' => rand(5, 120),           // 5 min - 2 hours (faster payment)
            };
            
            $paidAt = $paymentStatus === Payment::STATUS_PAID ? 
                 (clone $createdAt)->modify("+{$paymentDelay} minutes") : null;

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'gateway_transaction_id' => strtoupper(Str::random(10)),
                'gateway_reference' => strtoupper(Str::random(8)),
                'pnbp_receipt_no' => $paymentStatus === Payment::STATUS_PAID ? 'PNBP-' . $createdAt->format('Ymd') . '-' . rand(1000, 9999) : null,
                'amount' => $order->total_amount,
                'status' => $paymentStatus,
                'paid_at' => $paidAt,
                'expires_at' => (clone $createdAt)->modify('+1 day'),
                'payment_ip' => '127.0.0.1',
                'notes' => $paymentStatus === Payment::STATUS_PAID ? 'Demo paid payment' : 'Demo pending payment',
            ]);

            // Create shipment when needed with realistic timing progression
            if ($order->is_delivery) {
                $shipmentStatus = match ($order->status) {
                    Order::STATUS_COMPLETED => Shipment::STATUS_DELIVERED,
                    default => Shipment::STATUS_PENDING,
                };

                // Realistic processing and shipping timing
                $processingDays = match ($customerType) {
                    'individual_farmer' => rand(1, 3),       // 1-3 days processing
                    'cooperative' => rand(2, 5),             // 2-5 days (bulk orders need more time)
                    'institution' => rand(1, 4),             // 1-4 days (priority but complex)
                };
                
                $shippedAt = null;
                $deliveredAt = null;
                
                if ($order->status === Order::STATUS_COMPLETED) {
                     $baseTime = $paidAt ?? $createdAt;
                     $shippedAt = (clone $baseTime)->modify("+{$processingDays} days");
                     
                     $deliveryDays = rand(1, 4); // 1-4 days delivery time
                     $deliveredAt = (clone $shippedAt)->modify("+{$deliveryDays} days");
                 }

                Shipment::create([
                    'order_id' => $order->id,
                    'shipping_method' => Shipment::SHIPPING_DELIVERY,
                    'courier_name' => $order->courier_name,
                    'tracking_number' => $shippedAt ? strtoupper('TRK-' . Str::random(10)) : null,
                    'status' => $shipmentStatus,
                    'shipped_at' => $shippedAt,
                    'delivered_at' => $deliveredAt,
                ]);
            } elseif ($order->is_pickup) {
                $shipmentStatus = match ($order->status) {
                    Order::STATUS_PICKUP_READY => Shipment::STATUS_READY_FOR_PICKUP,
                    Order::STATUS_COMPLETED => Shipment::STATUS_DELIVERED,
                    default => Shipment::STATUS_PENDING,
                };

                // Realistic pickup timing
                $readyAt = null;
                $deliveredAt = null;
                
                if (in_array($order->status, [Order::STATUS_PICKUP_READY, Order::STATUS_COMPLETED])) {
                    $baseTime = $paidAt ?? $createdAt;
                    $processingDays = match ($customerType) {
                        'individual_farmer' => rand(1, 2),   // 1-2 days for pickup preparation
                        'cooperative' => rand(2, 4),         // 2-4 days (bulk orders)
                        'institution' => rand(1, 3),         // 1-3 days
                    };
                    $readyAt = (clone $baseTime)->modify("+{$processingDays} days");
                     
                     if ($order->status === Order::STATUS_COMPLETED) {
                         $pickupDelay = rand(0, 3); // 0-3 days after ready
                         $deliveredAt = (clone $readyAt)->modify("+{$pickupDelay} days");
                     }
                }

                Shipment::create([
                    'order_id' => $order->id,
                    'shipping_method' => Shipment::SHIPPING_PICKUP,
                    'status' => $shipmentStatus,
                    'ready_for_pickup_at' => $readyAt,
                    'delivered_at' => $deliveredAt,
                ]);
            }

            $createdCount++;
        }

        $this->command->info("DemoOrderSeeder: Created {$createdCount} demo orders with items, payments, shipments, and stock reservation.");
    }
}
