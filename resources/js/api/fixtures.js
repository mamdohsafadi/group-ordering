/**
 * Demo fixtures mirroring the legacy tables (`restaurant`, `dish`,
 * `user_address`) until the real /api/v1 endpoints land. Field names match
 * the models so swapping to live data is a find-and-replace-free change.
 */

export const restaurants = [
    {
        id: 1,
        name: 'Shawarma House',
        arabic_name: 'بيت الشاورما',
        active: 1,
        tagline: 'Levantine grill & wraps',
    },
];

export const dishes = [
    { id: 1, restaurant_id: 1, name: 'شاورما دجاج', eng_name: 'Chicken Shawarma Wrap', price: 25.0, active: 1 },
    { id: 2, restaurant_id: 1, name: 'شاورما لحمة', eng_name: 'Beef Shawarma Plate', price: 42.5, active: 1 },
    { id: 3, restaurant_id: 1, name: 'فروج مشوي', eng_name: 'Grilled Half Chicken', price: 55.0, active: 1 },
    { id: 4, restaurant_id: 1, name: 'حمص', eng_name: 'Hummus Bowl', price: 12.0, active: 1 },
    { id: 5, restaurant_id: 1, name: 'متبل', eng_name: 'Mutabbal', price: 12.0, active: 1 },
    { id: 6, restaurant_id: 1, name: 'بطاطا مقلية', eng_name: 'French Fries', price: 10.0, active: 1 },
    { id: 7, restaurant_id: 1, name: 'تبولة', eng_name: 'Tabbouleh Salad', price: 15.0, active: 1 },
    { id: 8, restaurant_id: 1, name: 'عصير ليمون بالنعناع', eng_name: 'Mint Lemonade', price: 9.5, active: 1 },
];

export const addresses = [
    { id: 1, name: 'Home', details: 'Damascus, Mazzeh, Building 12' },
    { id: 2, name: 'Office', details: 'Damascus, Baramkeh, Tradinos HQ' },
];

export function findRestaurant(id) {
    return restaurants.find((r) => r.id === Number(id)) ?? null;
}

export function dishesFor(restaurantId) {
    return dishes.filter((d) => d.restaurant_id === Number(restaurantId) && d.active === 1);
}
