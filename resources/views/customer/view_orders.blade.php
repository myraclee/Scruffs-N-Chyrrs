@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/view_orders.css'])
@endsection

@section('content')

{{-- Background Sparkles --}}
<div class="cart_sparkles_bg" aria-hidden="true">
    <span class="cart_spark s1">✦</span>
    <span class="cart_spark s2">✧</span>
    <span class="cart_spark s3">✦</span>
    <span class="cart_spark s4">✧</span>
    <span class="cart_spark s5">✦</span>
    <span class="cart_spark s6">✧</span>
    <span class="cart_spark s7">✦</span>
    <span class="cart_spark s8">✧</span>
</div>

<div class="cart_container">
    
    {{-- Page Header --}}
    <div class="cart_page_header">
        <div class="cart_header_sparkles">
            <span>✦</span>
            <h1 class="cart_page_title">Shopping Cart</h1>
            <span>✦</span>
        </div>
        <p class="cart_page_subtitle">Review your custom printing orders</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="cart_alert cart_alert_success">
            <span class="alert_icon">✓</span>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="cart_alert cart_alert_error">
            <span class="alert_icon">✕</span>
            {{ session('error') }}
        </div>
    @endif

    <div class="cart_main_grid">
        
        {{-- Cart Items Section --}}
        <div class="cart_items_section">
            
            @if(count($cartItems ?? []) > 0)
                
                <div class="cart_section_header">
                    <h2 class="cart_section_title">
                        <span class="title_sparkle">✦</span>
                        Your Items
                    </h2>
                    <span class="cart_items_count">{{ count($cartItems) }} {{ count($cartItems) === 1 ? 'item' : 'items' }}</span>
                </div>

                <div class="cart_items_list">
                    @foreach($cartItems as $index => $item)
                        <div class="cart_item_card" data-item-id="{{ $item['id'] ?? $index }}">
                            
                            {{-- Card Accent --}}
                            <div class="cart_item_accent"></div>
                            
                            {{-- Product Image --}}
                            <div class="cart_item_image">
                                <img src="{{ $item['product']['image'] ?? '/images/placeholder.png' }}" 
                                     alt="{{ $item['product']['name'] ?? 'Product' }}"
                                     loading="lazy">
                            </div>

                            {{-- Item Details --}}
                            <div class="cart_item_details">
                                
                                {{-- Product Name --}}
                                <h3 class="cart_item_name">{{ $item['product']['name'] ?? 'Custom Print' }}</h3>
                                
                                {{-- Selected Options --}}
                                <div class="cart_item_options">
                                    @foreach($item['options'] ?? [] as $optionLabel => $optionValue)
                                        <span class="cart_item_option_tag">
                                            <strong>{{ $optionLabel }}:</strong> {{ $optionValue }}
                                        </span>
                                    @endforeach
                                </div>

                                {{-- Google Drive Link --}}
                                <div class="cart_item_drive">
                                    <span class="drive_label">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                            <polyline points="13 2 13 9 20 9"></polyline>
                                        </svg>
                                        Drive:
                                    </span>
                                    <a href="{{ $item['drive_link'] ?? '#' }}" 
                                       target="_blank" 
                                       class="drive_link" 
                                       rel="noopener noreferrer">
                                        View Files
                                    </a>
                                </div>

                                {{-- File Specifications --}}
                                <div class="cart_item_files">
                                    <span class="files_label">Files:</span>
                                    <div class="files_list">
                                        @foreach($item['files'] ?? [] as $file)
                                            <div class="file_spec_chip">
                                                <span class="file_name">{{ $file['name'] }}</span>
                                                @if(!empty($file['layout']))
                                                    <span class="file_layout">({{ $file['layout'] }})</span>
                                                @endif
                                                <span class="file_pages">{{ $file['pages'] }}pg</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Rush Fee --}}
                                @if(!empty($item['rush_fee']))
                                    <div class="cart_item_rush">
                                        <span class="rush_badge">⚡ {{ $item['rush_fee']['label'] }}</span>
                                    </div>
                                @endif

                            </div>

                            {{-- Price & Actions --}}
                            <div class="cart_item_actions">
                                <div class="cart_item_price">
                                    <span class="price_label">Total</span>
                                    <span class="price_value">₱{{ number_format($item['total_price'] ?? 0, 2) }}</span>
                                </div>
                                
                                <div class="cart_item_buttons">
                                    <button class="cart_item_edit_btn" data-item-id="{{ $item['id'] ?? $index }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        Edit
                                    </button>
                                    <button class="cart_item_remove_btn" data-item-id="{{ $item['id'] ?? $index }}">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        Remove
                                    </button>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>

            @else
                
                {{-- Empty Cart State --}}
                <div class="cart_empty_state">
                    <div class="empty_state_sparkles" aria-hidden="true">
                        <span>✦</span><span>✧</span><span>✦</span>
                    </div>
                    <div class="empty_state_icon">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    </div>
                    <h2 class="empty_state_title">Your cart is empty</h2>
                    <p class="empty_state_text">Time to bring your imagination to life! ✨</p>
                    <a href="{{ route('products') }}" class="empty_state_btn">
                        <span>Browse Products</span>
                        <span class="btn_arrow">→</span>
                    </a>
                </div>

            @endif

        </div>

        {{-- Checkout Sidebar --}}
        @if(count($cartItems ?? []) > 0)
            <div class="cart_checkout_section">
                
                <div class="checkout_card">
                    
                    <h3 class="checkout_title">
                        <span class="title_sparkle">✦</span>
                        Order Summary
                    </h3>

                    {{-- Price Breakdown --}}
                    <div class="checkout_breakdown">
                        <div class="breakdown_row">
                            <span class="breakdown_label">Subtotal</span>
                            <span class="breakdown_value">₱{{ number_format($summary['subtotal'] ?? 0, 2) }}</span>
                        </div>

                        @if(($summary['layout_fees'] ?? 0) > 0)
                            <div class="breakdown_row">
                                <span class="breakdown_label">Layout Fees</span>
                                <span class="breakdown_value">₱{{ number_format($summary['layout_fees'], 2) }}</span>
                            </div>
                        @endif

                        @if(($summary['bulk_discount'] ?? 0) > 0)
                            <div class="breakdown_row discount">
                                <span class="breakdown_label">Bulk Discount</span>
                                <span class="breakdown_value">-₱{{ number_format($summary['bulk_discount'], 2) }}</span>
                            </div>
                        @endif

                        @if(($summary['rush_fees'] ?? 0) > 0)
                            <div class="breakdown_row">
                                <span class="breakdown_label">Rush Fees</span>
                                <span class="breakdown_value">₱{{ number_format($summary['rush_fees'], 2) }}</span>
                            </div>
                        @endif

                        <div class="breakdown_divider"></div>

                        <div class="breakdown_row total">
                            <span class="breakdown_label">Total</span>
                            <span class="breakdown_value">₱{{ number_format($summary['total'] ?? 0, 2) }}</span>
                        </div>
                    </div>

                    {{-- Special Instructions --}}
                    <form action="{{ route('cart.checkout') }}" method="POST" class="checkout_form">
                        @csrf
                        
                        <div class="checkout_instructions">
                            <label for="specialInstructions" class="instructions_label">
                                <span class="label_sparkle">✧</span>
                                Special Instructions
                                <span class="label_optional">(Optional)</span>
                            </label>
                            <textarea
                                id="specialInstructions"
                                name="special_instructions"
                                class="instructions_textarea"
                                placeholder="Any special requests for your entire order..."
                                maxlength="1000"
                            ></textarea>
                            <small class="instructions_count">
                                <span id="charCount">0</span>/1000
                            </small>
                        </div>

                        <button type="submit" class="checkout_btn">
                            <span class="btn_sparkle">✦</span>
                            <span>Proceed to Checkout</span>
                            <span class="btn_arrow">→</span>
                        </button>
                    </form>

                </div>

                {{-- Continue Shopping Link --}}
                <a href="{{ route('products') }}" class="continue_shopping_link">
                    <span class="link_arrow">←</span>
                    Continue Shopping
                </a>

            </div>
        @endif

    </div>

</div>

@endsection

@section('page_scripts')
<script>
// Character counter for special instructions
document.getElementById('specialInstructions')?.addEventListener('input', function(e) {
    const count = e.target.value.length;
    document.getElementById('charCount').textContent = count;
});

// Remove item from cart
document.querySelectorAll('.cart_item_remove_btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const itemId = this.dataset.itemId;
        if (confirm('Remove this item from your cart?')) {
            // Send AJAX request to remove item
            fetch(`/cart/remove/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
});

// Edit item (redirect to product page with item data)
document.querySelectorAll('.cart_item_edit_btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const itemId = this.dataset.itemId;
        // Implement edit functionality - could open modal or redirect to product page
        console.log('Edit item:', itemId);
    });
});
</script>
@endsection