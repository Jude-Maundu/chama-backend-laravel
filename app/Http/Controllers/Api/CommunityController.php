<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chama;
use App\Models\MarketplaceItem;
use App\Models\InsuranceProduct;
use App\Models\CharityModule;
use App\Models\EducationalContent;
use App\Models\JobPosting;
use App\Models\BusinessShowcase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunityController extends Controller
{
    /**
     * Marketplace (Feature 44)
     */
    public function getMarketplaceItems(Chama $chama)
    {
        $items = MarketplaceItem::where('chama_id', $chama->id)
                               ->with(['user', 'currency'])
                               ->orderByDesc('created_at')
                               ->get();
        return response()->json(['success' => true, 'data' => $items]);
    }

    public function storeMarketplaceItem(Request $request, Chama $chama)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
            'type' => 'required|in:good,service',
            'images' => 'nullable|array',
        ]);

        $item = MarketplaceItem::create(array_merge($validated, [
            'chama_id' => $chama->id,
            'user_id' => Auth::id(),
            'status' => 'available'
        ]));

        return response()->json(['success' => true, 'data' => $item], 201);
    }

    /**
     * Group Insurance (Feature 45)
     */
    public function getInsuranceProducts(Chama $chama)
    {
        $products = InsuranceProduct::where('chama_id', $chama->id)
                                   ->with('currency')
                                   ->get();
        return response()->json(['success' => true, 'data' => $products]);
    }

    public function storeInsuranceProduct(Request $request, Chama $chama)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string|max:255',
            'description' => 'required|string',
            'premium_amount' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
            'coverage_details' => 'required|string',
        ]);

        $product = InsuranceProduct::create(array_merge($validated, [
            'chama_id' => $chama->id,
            'status' => 'active'
        ]));

        return response()->json(['success' => true, 'data' => $product], 201);
    }

    /**
     * Charity/Giving (Feature 46)
     */
    public function getCharityModules(Chama $chama)
    {
        $modules = CharityModule::where('chama_id', $chama->id)
                               ->with('currency')
                               ->get();
        return response()->json(['success' => true, 'data' => $modules]);
    }

    public function storeCharityModule(Request $request, Chama $chama)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'target_amount' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
        ]);

        $module = CharityModule::create(array_merge($validated, [
            'chama_id' => $chama->id,
            'current_amount' => 0,
            'status' => 'ongoing'
        ]));

        return response()->json(['success' => true, 'data' => $module], 201);
    }

    /**
     * Educational Content (Feature 47)
     */
    public function getEducationalContent()
    {
        $content = EducationalContent::orderByDesc('created_at')->get();
        return response()->json(['success' => true, 'data' => $content]);
    }

    public function storeEducationalContent(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content_type' => 'required|in:video,pdf,article',
            'url' => 'required|url',
            'is_premium' => 'boolean',
        ]);

        $content = EducationalContent::create($validated);
        return response()->json(['success' => true, 'data' => $content], 201);
    }

    /**
     * Job Board (Feature 48)
     */
    public function getJobPostings(Chama $chama)
    {
        $jobs = JobPosting::where('chama_id', $chama->id)
                          ->with('user')
                          ->orderByDesc('created_at')
                          ->get();
        return response()->json(['success' => true, 'data' => $jobs]);
    }

    public function storeJobPosting(Request $request, Chama $chama)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'company' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'salary_range' => 'nullable|string|max:255',
        ]);

        $job = JobPosting::create(array_merge($validated, [
            'chama_id' => $chama->id,
            'user_id' => Auth::id(),
            'status' => 'open'
        ]));

        return response()->json(['success' => true, 'data' => $job], 201);
    }

    /**
     * Business Showcase (Feature 49)
     */
    public function getBusinessShowcases(Chama $chama)
    {
        $showcases = BusinessShowcase::where('chama_id', $chama->id)
                                     ->with('user')
                                     ->get();
        return response()->json(['success' => true, 'data' => $showcases]);
    }

    public function storeBusinessShowcase(Request $request, Chama $chama)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'description' => 'required|string',
            'industry' => 'required|string|max:255',
            'logo' => 'nullable|string',
            'website' => 'nullable|url',
            'contact_info' => 'required|string',
        ]);

        $showcase = BusinessShowcase::updateOrCreate(
            ['user_id' => Auth::id(), 'chama_id' => $chama->id],
            $validated
        );

        return response()->json(['success' => true, 'data' => $showcase], 201);
    }
}
