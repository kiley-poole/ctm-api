<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|Response
     */
    public function index()
    {
        return CustomerResource::collection(Customer::paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return CustomerResource|\Illuminate\Http\JsonResponse|Response
     */
    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'email' => 'required|email|string|max:255|unique:customers,email',
            'opt_in' => 'required|boolean',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255'
        ]);

        DB::beginTransaction();
        try{
            $new_customer = $customer->create($data);
            DB::commit();
        } catch(\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return response()->json([
                'message' => 'create new customer failed'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new CustomerResource($new_customer);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return CustomerResource|Response
     */
    public function show($id, Customer $customer)
    {
        return new CustomerResource($customer->findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return CustomerResource|\Illuminate\Http\JsonResponse|Response
     */
    public function update(Request $request, $id, Customer $customer)
    {
        $data = $request->validate([
            'email' => 'email|string|max:255|unique:customers,email',
            'opt_in' => 'boolean',
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255'
        ]);

        $customer_to_update = $customer->findOrFail($id);

        DB::beginTransaction();
        try{
            $customer_to_update->update($data);
            DB::commit();
        } catch(\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return response()->json([
                'message' => 'failed to update customer'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new CustomerResource($customer_to_update);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function destroy($id, Customer $customer)
    {
        $customer_to_delete = $customer->findOrFail($id);
        DB::beginTransaction();
        try{
            $customer_to_delete->delete();
            DB::commit();
            return response()->json([
                'message' => 'customer deleted'],
                Response::HTTP_NO_CONTENT
            );
        } catch(\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return response()->json([
                'message' => 'failed to update customer'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function toggleOptin($id, Customer $customer)
    {
        $customer_to_optin_toggle = $customer->findOrFail($id);
        DB::beginTransaction();
        try{
            $customer_to_optin_toggle->opt_in = !$customer_to_optin_toggle->opt_in;
            $customer_to_optin_toggle->save();
            DB::commit();
        } catch(\Exception $e) {
            Log::info($e);
            DB::rollBack();
            return response()->json([
                'message' => 'failed to toggle customer opt-in flag'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new CustomerResource($customer_to_optin_toggle);
    }
}
