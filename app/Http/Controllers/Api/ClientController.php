<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with('classes');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('client_name', 'like', "%{$request->search}%")
                  ->orWhere('client_code', 'like', "%{$request->search}%");
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->active_only) {
            $query->where('is_active', true);
        }

        $clients = $query->orderBy('client_name')->get();

        return response()->json($clients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_code' => 'required|string|max:20|unique:clients',
            'client_name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'ledger_name' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:10',
            'mobile' => 'nullable|string|max:15',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:150',
            'category' => 'nullable|in:insurance,corporate,trust,charity,government,other',
            'rate_based_on' => 'nullable|in:standard,normal,increase,decrease,cashless_price_list',
            'rate_adjustment_percent' => 'nullable|numeric|min:0|max:100',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
        ]);

        $client = Client::create($validated);

        return response()->json($client->load('classes'), 201);
    }

    public function show(Client $client)
    {
        return response()->json($client->load('classes'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'client_name' => 'sometimes|required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'ledger_name' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:10',
            'mobile' => 'nullable|string|max:15',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'website' => 'nullable|string|max:150',
            'category' => 'nullable|in:insurance,corporate,trust,charity,government,other',
            'rate_based_on' => 'nullable|in:standard,normal,increase,decrease,cashless_price_list',
            'rate_adjustment_percent' => 'nullable|numeric|min:0|max:100',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $client->update($validated);

        return response()->json($client->load('classes'));
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Client deleted successfully']);
    }
}
