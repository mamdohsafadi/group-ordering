<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\GroupOrder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Demo screen only (handoff §3): menu in group context for US-004. Catalogue
 * data comes from the DB as props; cart actions go through /api/v1.
 */
class GroupOrderMenuController extends Controller
{
    /** Menu with modifier groups for the group's restaurant. */
    public function show(int $groupOrder): Response
    {
        $model = GroupOrder::query()->with('restaurant')->findOrFail($groupOrder);

        $dishes = Dish::query()
            ->where('restaurant_id', $model->restaurant_id)
            ->where('active', 1)
            ->with(['appliedOptions.dishOption.group'])
            ->orderBy('id')
            ->get()
            ->map(fn (Dish $dish) => [
                'id' => $dish->id,
                'name' => $dish->name,
                'eng_name' => $dish->eng_name,
                'price' => $dish->price,
                'option_groups' => $dish->appliedOptions
                    ->map(fn ($applied) => $applied->dishOption)
                    ->filter(fn ($option) => $option !== null && $option->is_active && ! $option->is_deleted)
                    ->groupBy('dish_group_id')
                    ->map(fn ($options) => [
                        'id' => $options->first()->group->id,
                        'name' => $options->first()->group->en_name,
                        'options' => $options->map(fn ($option) => [
                            'id' => $option->id,
                            'name' => $option->en_name,
                            'price' => (float) $option->price,
                        ])->values(),
                    ])
                    ->values(),
            ]);

        return Inertia::render('GroupOrders/Menu', [
            'groupOrderId' => $model->id,
            'restaurant' => $model->restaurant->only('id', 'name', 'arabic_name', 'tagline'),
            'dishes' => $dishes,
        ]);
    }
}
