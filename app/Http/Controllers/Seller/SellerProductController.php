<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\ApiController;
use App\Product;
use App\Seller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SellerProductController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Seller $seller)
    {
        //
        $products = $seller->products;

        return $this->showAll($products);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $seller)
    {
        //

        $rules = [
            'name' => 'required',
            'description' => 'required',
            'quantity' => 'required|integer|min:1',
            'image' => 'required|image',
        ];

        $this->validate($request, $rules);

        $data = $request->all();

        $data["status"] = Product::UNAVAILABLE_PRODUCT;
        //$data["image"]  = 'http://172.17.28.219:8888/imgs/1.jpg';
        $data["image"]  = $request->image->store('');
        $data["seller_id"] = $seller->id;

        $product = Product::create($data);

        return $this->showOne($product);

    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Seller  $seller
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Seller $seller, Product $product)
    {
        //
        $rules = [
            //'name' => 'required',
            'quantity' => 'integer|min:1',
            'status'=> 'in:'.Product::AVAILABLE_PRODUCT.','.Product::UNAVAILABLE_PRODUCT,
            'image' => 'image',
        ];

        $this->validate($request, $rules);

        // $this->checkSeller($product, $seller);

        $product->fill($request->only([
            'name',
            'quantity',
            'description',
        ]));

        if($request->has('status'))
        {
            $product->status = $request->status;

            if($product->isAvailable() && $product->categories()->count() == 0)
            {
                return $this->errorResponse('This active product must have at least one category',409);
            }
        }

        if($product->isClean())
        {
            return $this->errorResponse('You need to specify different value to update', 422);
        }

        if($request->hasFile('image'))
        {
            Storage::delete($product->image);

            $product->image = $request->image->store('');
        }

        $product->save();

        return $this->showOne($product);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Seller  $seller
     * @return \Illuminate\Http\Response
     */
    public function destroy(Seller $seller, Product $product)
    {
        //
        $this->checkSeller($product, $seller);

        $product->delete();
        Storage::delete($product->image);

        return $this->showOne($product);
    }

    protected function checkSeller(Product $product, Seller $seller)
    {
        if($product->seller_id != $seller->id){
            throw new HttpException(422,'This product does not belong to this seller');
        }
    }
}
