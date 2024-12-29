<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Shipping;
use App\Notifications\StatusNotification;
use App\User;
use PDF;
use Notification;
use Helper;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = Order::orderBy('id', 'DESC')->paginate(10);
        return view('backend.order.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Unused method, can be implemented later if required
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'string|required',
            'last_name' => 'string|required',
            'address1' => 'string|required',
            'address2' => 'string|nullable',
            'coupon' => 'nullable|numeric',
            'phone' => 'numeric|required',
            'post_code' => 'string|nullable',
            'email' => 'string|required|email',
        ]);

        $cart = Cart::where('user_id', auth()->user()->id)->where('order_id', null)->first();

        if (!$cart) {
            $request->session()->flash('error', 'Cart is Empty!');
            return back();
        }

        $order = new Order();
        $orderData = $validatedData;
        $orderData['order_number'] = 'ORD-' . strtoupper(Str::random(10));
        $orderData['user_id'] = auth()->user()->id;
        $orderData['shipping_id'] = $request->shipping;

        $shippingPrice = Shipping::where('id', $orderData['shipping_id'])->value('price');
        $orderData['sub_total'] = Helper::totalCartPrice();
        $orderData['quantity'] = Helper::cartCount();

        if (session('coupon')) {
            $orderData['coupon'] = session('coupon')['value'];
        }

        $orderData['total_amount'] = Helper::totalCartPrice() + ($shippingPrice ?? 0) - (session('coupon')['value'] ?? 0);
        $orderData['status'] = 'new';
        $orderData['payment_method'] = $request->payment_method === 'paypal' ? 'paypal' : 'cod';
        $orderData['payment_status'] = $request->payment_method === 'paypal' ? 'paid' : 'unpaid';

        $order->fill($orderData);
        $order->save();

        Cart::where('user_id', auth()->user()->id)->where('order_id', null)->update(['order_id' => $order->id]);

        $adminUser = User::where('role', 'admin')->first();
        $details = [
            'title' => 'New order created',
            'actionURL' => route('order.show', $order->id),
            'fas' => 'fa-file-alt',
        ];

        Notification::send($adminUser, new StatusNotification($details));

        session()->forget(['cart', 'coupon']);

        $request->session()->flash('success', 'Your product has been successfully placed in order');
        return redirect()->route('home');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $order = Order::findOrFail($id);
        return view('backend.order.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $order = Order::findOrFail($id);
        return view('backend.order.edit', compact('order'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validatedData = $request->validate([
            'status' => ['required', Rule::in(['new', 'process', 'delivered', 'cancel'])],
        ]);

        if ($validatedData['status'] === 'delivered') {
            foreach ($order->cart as $cart) {
                $product = $cart->product;
                $product->stock -= $cart->quantity;
                $product->save();
            }
        }

        $order->update($validatedData);

        $request->session()->flash('success', 'Successfully updated order');
        return redirect()->route('order.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        request()->session()->flash('success', 'Order Successfully deleted');
        return redirect()->route('order.index');
    }

    /**
     * Display the order tracking page.
     */
    public function orderTrack()
    {
        return view('frontend.pages.order-track');
    }

    /**
     * Track the order by order number.
     */
    public function productTrackOrder(Request $request)
    {
        $order = Order::where('user_id', auth()->user()->id)->where('order_number', $request->order_number)->first();

        if ($order) {
            $messages = [
                'new' => 'Your order has been placed. Please wait.',
                'process' => 'Your order is under processing. Please wait.',
                'delivered' => 'Your order has been successfully delivered.',
                'cancel' => 'Your order has been canceled. Please try again.',
            ];

            $statusMessage = $messages[$order->status] ?? 'Invalid status';
            request()->session()->flash('success', $statusMessage);
        } else {
            request()->session()->flash('error', 'Invalid order number. Please try again.');
        }

        return redirect()->route('home');
    }

    /**
     * Generate a PDF for the specified order.
     */
    public function pdf(Request $request)
    {
        set_time_limit(300); 
        $order = Order::findOrFail($request->id);
        $fileName = $order->order_number . '-' . $order->first_name . '.pdf';

        $pdf = PDF::loadView('backend.order.pdf', compact('order'));
        return $pdf->download($fileName);
    }

    /**
     * Generate income chart data for the current year.
     */
    public function incomeChart()
    {
        $year = Carbon::now()->year;

        $orders = Order::with('cart_info')
            ->whereYear('created_at', $year)
            ->where('status', 'delivered')
            ->get()
            ->groupBy(function ($order) {
                return Carbon::parse($order->created_at)->format('m');
            });

        $result = [];

        foreach ($orders as $month => $orderGroup) {
            $result[intval($month)] = $orderGroup->sum(fn($order) => $order->cart_info->sum('amount'));
        }

        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthName = date('F', mktime(0, 0, 0, $i, 1));
            $data[$monthName] = $result[$i] ?? 0.0;
        }

        return $data;
    }
}
