<?php

namespace Database\Seeders;

use App\Models\AppliedDishOption;
use App\Models\Dish;
use App\Models\DishOption;
use App\Models\DishOptionGroup;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

/**
 * Curated demo catalogue: 10 restaurants with realistic bilingual menus and
 * modifier groups on the dishes where they make sense, so the mentor can
 * browse real-looking data end to end. Idempotent — safe to re-run.
 */
class DemoRestaurantSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalogue() as $entry) {
            $restaurant = Restaurant::updateOrCreate(
                ['name' => $entry['name']],
                [
                    'arabic_name' => $entry['arabic_name'],
                    'tagline' => $entry['tagline'],
                    'active' => 1,
                ],
            );

            $dishes = [];

            foreach ($entry['dishes'] as [$arabic, $english, $price]) {
                $dishes[$english] = Dish::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'eng_name' => $english],
                    ['name' => $arabic, 'price' => $price, 'active' => 1],
                );
            }

            foreach ($entry['option_groups'] ?? [] as $groupData) {
                $group = DishOptionGroup::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'en_name' => $groupData['en_name']],
                    ['ar_name' => $groupData['ar_name'], 'is_active' => true, 'is_deleted' => false],
                );

                $options = [];

                foreach ($groupData['options'] as [$arName, $enName, $price, $isDefault]) {
                    $options[] = DishOption::updateOrCreate(
                        ['dish_group_id' => $group->id, 'en_name' => $enName],
                        [
                            'ar_name' => $arName,
                            'price' => $price,
                            'purchase_price' => round($price * 0.7, 2),
                            'is_default' => $isDefault,
                            'is_active' => true,
                            'is_deleted' => false,
                        ],
                    );
                }

                foreach ($groupData['applies_to'] as $dishEnglishName) {
                    $dish = $dishes[$dishEnglishName] ?? null;

                    if ($dish === null) {
                        continue;
                    }

                    foreach ($options as $option) {
                        AppliedDishOption::firstOrCreate([
                            'dish_id' => $dish->id,
                            'dish_option_id' => $option->id,
                        ]);
                    }
                }
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function catalogue(): array
    {
        return [
            [
                'name' => 'Shawarma House',
                'arabic_name' => 'بيت الشاورما',
                'tagline' => 'Levantine grill & wraps',
                'dishes' => [
                    ['شاورما دجاج', 'Chicken Shawarma Wrap', 25.00],
                    ['شاورما لحمة', 'Beef Shawarma Plate', 42.50],
                    ['فروج مشوي', 'Grilled Half Chicken', 55.00],
                    ['حمص', 'Hummus Bowl', 12.00],
                    ['متبل', 'Mutabbal', 12.00],
                    ['بطاطا مقلية', 'French Fries', 10.00],
                    ['تبولة', 'Tabbouleh Salad', 15.00],
                    ['عصير ليمون بالنعناع', 'Mint Lemonade', 9.50],
                ],
                'option_groups' => [
                    [
                        'en_name' => 'Wrap Size',
                        'ar_name' => 'حجم السندويشة',
                        'options' => [
                            ['عادي', 'Regular', 0.00, true],
                            ['كبير', 'Large', 6.00, false],
                        ],
                        'applies_to' => ['Chicken Shawarma Wrap'],
                    ],
                    [
                        'en_name' => 'Extras',
                        'ar_name' => 'إضافات',
                        'options' => [
                            ['جبنة', 'Extra Cheese', 3.00, false],
                            ['ثومية', 'Garlic Dip', 2.00, false],
                            ['مخلل', 'Pickles', 1.50, false],
                        ],
                        'applies_to' => ['Chicken Shawarma Wrap', 'Beef Shawarma Plate', 'Grilled Half Chicken'],
                    ],
                ],
            ],
            [
                'name' => 'Damascus Grill',
                'arabic_name' => 'مشاوي الشام',
                'tagline' => 'Charcoal kebab & mezze',
                'dishes' => [
                    ['كباب حلبي', 'Aleppo Kebab', 48.00],
                    ['شقف مشوي', 'Grilled Lamb Skewers', 62.00],
                    ['شيش طاووق', 'Shish Tawook', 45.00],
                    ['كفتة بالطحينة', 'Kofta with Tahini', 50.00],
                    ['فتوش', 'Fattoush Salad', 14.00],
                    ['بابا غنوج', 'Baba Ghanouj', 13.00],
                    ['أرز بالشعيرية', 'Vermicelli Rice', 8.00],
                ],
                'option_groups' => [
                    [
                        'en_name' => 'Spice Level',
                        'ar_name' => 'درجة الحرارة',
                        'options' => [
                            ['عادي', 'Mild', 0.00, true],
                            ['حار', 'Spicy', 0.00, false],
                            ['حار جداً', 'Extra Spicy', 0.00, false],
                        ],
                        'applies_to' => ['Aleppo Kebab', 'Shish Tawook'],
                    ],
                ],
            ],
            [
                'name' => 'Pizza Roma',
                'arabic_name' => 'بيتزا روما',
                'tagline' => 'Stone-oven Italian pizza',
                'dishes' => [
                    ['بيتزا مارغريتا', 'Margherita Pizza', 35.00],
                    ['بيتزا بيبروني', 'Pepperoni Pizza', 45.00],
                    ['بيتزا خضار', 'Veggie Pizza', 38.00],
                    ['بيتزا رباعية الأجبان', 'Four Cheese Pizza', 48.00],
                    ['سلطة سيزر', 'Caesar Salad', 22.00],
                    ['خبز بالثوم', 'Garlic Bread', 12.00],
                ],
                'option_groups' => [
                    [
                        'en_name' => 'Pizza Size',
                        'ar_name' => 'حجم البيتزا',
                        'options' => [
                            ['صغير', 'Small', 0.00, true],
                            ['وسط', 'Medium', 8.00, false],
                            ['كبير', 'Large', 15.00, false],
                        ],
                        'applies_to' => ['Margherita Pizza', 'Pepperoni Pizza', 'Veggie Pizza', 'Four Cheese Pizza'],
                    ],
                    [
                        'en_name' => 'Crust',
                        'ar_name' => 'العجينة',
                        'options' => [
                            ['رفيعة', 'Thin Crust', 0.00, true],
                            ['سميكة', 'Thick Crust', 3.00, false],
                            ['محشية جبنة', 'Cheese Stuffed', 7.00, false],
                        ],
                        'applies_to' => ['Margherita Pizza', 'Pepperoni Pizza'],
                    ],
                ],
            ],
            [
                'name' => 'Burger Factory',
                'arabic_name' => 'مصنع البرغر',
                'tagline' => 'Smash burgers & shakes',
                'dishes' => [
                    ['برغر كلاسيك', 'Classic Burger', 32.00],
                    ['تشيز برغر مزدوج', 'Double Cheeseburger', 44.00],
                    ['برغر دجاج مقرمش', 'Crispy Chicken Burger', 36.00],
                    ['برغر مشروم', 'Mushroom Swiss Burger', 40.00],
                    ['بطاطا بالجبنة', 'Cheese Fries', 16.00],
                    ['ميلك شيك فانيلا', 'Vanilla Milkshake', 18.00],
                    ['ميلك شيك شوكولا', 'Chocolate Milkshake', 18.00],
                ],
                'option_groups' => [
                    [
                        'en_name' => 'Patty',
                        'ar_name' => 'شريحة اللحم',
                        'options' => [
                            ['واحدة', 'Single', 0.00, true],
                            ['مزدوجة', 'Double', 10.00, false],
                        ],
                        'applies_to' => ['Classic Burger', 'Mushroom Swiss Burger'],
                    ],
                    [
                        'en_name' => 'Add-ons',
                        'ar_name' => 'إضافات',
                        'options' => [
                            ['جبنة إضافية', 'Extra Cheese', 3.00, false],
                            ['بيكون حبش', 'Turkey Bacon', 5.00, false],
                            ['بصل مكرمل', 'Caramelized Onion', 2.50, false],
                        ],
                        'applies_to' => ['Classic Burger', 'Double Cheeseburger', 'Crispy Chicken Burger'],
                    ],
                ],
            ],
            [
                'name' => 'Falafel Corner',
                'arabic_name' => 'زاوية الفلافل',
                'tagline' => 'Street food classics since forever',
                'dishes' => [
                    ['ساندويش فلافل', 'Falafel Sandwich', 8.00],
                    ['صحن فلافل', 'Falafel Plate', 15.00],
                    ['فول مدمس', 'Foul Mudammas', 12.00],
                    ['فتة حمص', 'Hummus Fatteh', 18.00],
                    ['مسبحة', 'Msabbaha', 14.00],
                    ['شاي عالفحم', 'Charcoal Tea', 4.00],
                ],
            ],
            [
                'name' => 'Sushi Tokyo',
                'arabic_name' => 'سوشي طوكيو',
                'tagline' => 'Fresh rolls & sashimi',
                'dishes' => [
                    ['رول كاليفورنيا', 'California Roll', 52.00],
                    ['رول سلمون', 'Salmon Roll', 58.00],
                    ['سوشي سلمون (٨ قطع)', 'Salmon Nigiri (8 pcs)', 65.00],
                    ['رول تمبورا', 'Tempura Roll', 55.00],
                    ['حساء ميسو', 'Miso Soup', 15.00],
                    ['سلطة أعشاب بحرية', 'Seaweed Salad', 20.00],
                ],
                'option_groups' => [
                    [
                        'en_name' => 'Sides',
                        'ar_name' => 'مرافقات',
                        'options' => [
                            ['واسابي إضافي', 'Extra Wasabi', 2.00, false],
                            ['زنجبيل إضافي', 'Extra Ginger', 2.00, false],
                            ['صويا صلصة', 'Soy Sauce', 0.00, true],
                        ],
                        'applies_to' => ['California Roll', 'Salmon Roll', 'Tempura Roll'],
                    ],
                ],
            ],
            [
                'name' => 'Pasta Casa',
                'arabic_name' => 'بيت الباستا',
                'tagline' => 'Handmade pasta, Italian heart',
                'dishes' => [
                    ['سباغيتي بولونيز', 'Spaghetti Bolognese', 38.00],
                    ['فيتوتشيني ألفريدو', 'Fettuccine Alfredo', 40.00],
                    ['بيني أرابياتا', 'Penne Arrabbiata', 34.00],
                    ['لازانيا لحمة', 'Beef Lasagna', 46.00],
                    ['ريزوتو فطر', 'Mushroom Risotto', 42.00],
                    ['تيراميسو', 'Tiramisu', 20.00],
                ],
                'option_groups' => [
                    [
                        'en_name' => 'Pasta Extras',
                        'ar_name' => 'إضافات الباستا',
                        'options' => [
                            ['دجاج مشوي', 'Grilled Chicken', 8.00, false],
                            ['جبنة بارميزان', 'Parmesan', 4.00, false],
                        ],
                        'applies_to' => ['Fettuccine Alfredo', 'Penne Arrabbiata'],
                    ],
                ],
            ],
            [
                'name' => 'Sweet Damascus',
                'arabic_name' => 'حلويات الشام',
                'tagline' => 'Oriental sweets & knafeh',
                'dishes' => [
                    ['كنافة نابلسية', 'Nabulsi Knafeh', 25.00],
                    ['بقلاوة مشكلة (كيلو)', 'Mixed Baklava (kg)', 90.00],
                    ['هريسة', 'Harisseh', 12.00],
                    ['حلاوة الجبن', 'Halawet El Jibn', 22.00],
                    ['مهلبية', 'Muhallebi', 10.00],
                    ['عوامة', 'Awameh', 14.00],
                ],
            ],
            [
                'name' => 'Broasted King',
                'arabic_name' => 'ملك البروستد',
                'tagline' => 'Crispy broasted, secret spice',
                'dishes' => [
                    ['وجبة بروستد ٤ قطع', 'Broasted Meal (4 pcs)', 38.00],
                    ['وجبة بروستد ٨ قطع', 'Broasted Meal (8 pcs)', 68.00],
                    ['زنجر ساندويش', 'Zinger Sandwich', 28.00],
                    ['كرسبي تندر', 'Crispy Tenders', 32.00],
                    ['كول سلو', 'Coleslaw', 8.00],
                    ['بطاطا ودجز', 'Potato Wedges', 12.00],
                ],
                'option_groups' => [
                    [
                        'en_name' => 'Dips',
                        'ar_name' => 'صلصات',
                        'options' => [
                            ['ثومية', 'Garlic Dip', 2.00, false],
                            ['كوكتيل', 'Cocktail Sauce', 2.00, false],
                            ['حارة', 'Hot Sauce', 2.00, false],
                        ],
                        'applies_to' => ['Broasted Meal (4 pcs)', 'Broasted Meal (8 pcs)', 'Crispy Tenders'],
                    ],
                ],
            ],
            [
                'name' => 'Fresh Juice Bar',
                'arabic_name' => 'عصائر فرش',
                'tagline' => 'Cold-pressed juices & smoothies',
                'dishes' => [
                    ['عصير برتقال طازج', 'Fresh Orange Juice', 12.00],
                    ['عصير رمان', 'Pomegranate Juice', 16.00],
                    ['سموذي مانغا', 'Mango Smoothie', 18.00],
                    ['سموذي فراولة موز', 'Strawberry Banana Smoothie', 18.00],
                    ['كوكتيل فواكه', 'Fruit Cocktail', 20.00],
                    ['عصير جزر وزنجبيل', 'Carrot Ginger Juice', 14.00],
                ],
                'option_groups' => [
                    [
                        'en_name' => 'Cup Size',
                        'ar_name' => 'حجم الكوب',
                        'options' => [
                            ['وسط', 'Medium', 0.00, true],
                            ['كبير', 'Large', 5.00, false],
                        ],
                        'applies_to' => ['Fresh Orange Juice', 'Mango Smoothie', 'Strawberry Banana Smoothie'],
                    ],
                ],
            ],
        ];
    }
}
