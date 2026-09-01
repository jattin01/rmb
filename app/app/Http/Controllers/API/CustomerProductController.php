<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiGenericException;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\CustomerProduct;
use Exception;
use Illuminate\Http\Request;

class CustomerProductController extends Controller
{
    public function show(Request $request, String $id)
    {
        try {
            $customerProduct = CustomerProduct::with('product') -> select('id', 'project_id', 'product_id', 'total_quantity', 'ordered_quantity') -> where([
                ['id', $request -> id],
                ['customer_id', $request -> customer_id],
                ['status', ConstantHelper::ACTIVE]
            ]) -> first();

            return array(
                'message' => __("message.records_returned_successfully", ['static' => __("static.product")]),
                'data' => array(
                    'customer_product' => $customerProduct
                )
            );

        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}
