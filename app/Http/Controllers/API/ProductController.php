<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $name = $request->input('name');
        $description = $request->input('description');
        $tags = $request->input('tags');
        $categories = $request->input('categories');
        $price_from = $request->input('price_from');
        $price_to = $request->input('price_to');

        if ($id) {
            $product = Product::with(['category', 'galleries'])->find($id);
            if ($product) {
                return ResponseFormatter::success($product, 'Data produk berhasil diambil');
            } else {
                return ResponseFormatter::error(null, 'Data produk tidak ada', 404);
            }
        }
        $product =  Product::with(['category', 'galleries']);
        if ($name) {
            $product->where('name', 'LIKE', '%' . $name . '%');
        }
        if ($description) {
            $product->where('description', 'LIKE', '%' . $description . '%');
        }
        if ($tags) {
            $product->where('tags', 'LIKE', '%' . $tags . '%');
        }
        if ($price_from) {
            $product->where('price', '>=', $price_from);
        }
        if ($price_to) {
            $product->where('price', '<=', $price_to);
        }
        if ($categories) {
            $product->where('price', $categories);
        }
        return ResponseFormatter::success($product->paginate($limit), 'Data produk berhasil diambil');
    }
    public function checkout(Request $request)
    {
        $request->validate(
            [
                'items' => 'required|array', //data yang ada di id menggunakan array jadi harus di tulis array
                'items.*.id' => 'exist:products,id', //mengambil banyaknya id yang ada di dalam product, product harus berasal dari backend
                'total_price' => 'required',
                'shipping_price' => 'required',
                'status' => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED', //mevalidasi keterangan ada di checkout terserah apapun
            ]
        );
        $transaction = Transaction::create(
            [
                'users_id' => Auth::user()->id,
                'address' => $request->address,
                'total_price' => $request->total_price,
                'shipping_price' => $request->shipping_price,
                'status' => $request->status,
            ]
        ); //menyesuaikan dengan table yang ada di database transaction dan dibutuhkan untuk membuat data transaksi

        foreach ($request->items as $product) {
            TransactionItem::create(
                [
                    'users_id' => Auth::user()->id,
                    'products_id' => $product['id'], //karena array jadi diambil dengan tipe array
                    'transaction_id' => $transaction->id, //diambil dari data transaction yang sudah dibuat di line 66
                    'quanity' => $product['quantity'], //jumlah dari harga
                ]
            );
        }
        return ResponseFormatter::success($transaction->load('items.product'), 'Transaksi Berhasil'); //setelah submit data nya belum ke update jadi bisa di load untuk ambil data yang sudah di submit sebelumnya
    }
}
