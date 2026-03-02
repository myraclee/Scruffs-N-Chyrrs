@extends('layouts.customer_layout')

@section('page_css')
@vite(['resources/css/customer/pages/faqs.css'])
@endsection

@section('page_js')
@vite('resources/js/customer/pages/faqs.js')
@endsection

@section('content')

    <div class="faqs_opening">
        <h1>Frequently Asked Questions</h1>
        <p>Have questions about our services? We've got answers!</p>
    </div>

    <div class="faqs_container_section">
        <div class="faqs_accordion_wrapper">
            <h2>General Questions</h2>

            <div class="faq_item">
                <details>
                    <summary>What products does Scruffs&Chyrrs offer?</summary>
                    <div class="faq_content">
                        <p>
                            We specialize in merchandise manufacturing for student artists and creators. Our services include:
                        </p>
                        <ul>
                            <li><strong>Stickers</strong> — Custom printed stickers with various finishes</li>
                            <li><strong>Prints</strong> — High-quality art prints and posters</li>
                            <li><strong>Button Pins</strong> — Custom-designed badges and pins</li>
                            <li><strong>Custom Cut Items</strong> — Uniquely shaped merchandise</li>
                        </ul>
                        <p>All products are manufactured with attention to detail and quality at affordable prices.</p>
                    </div>
                </details>
            </div>

            <div class="faq_item">
                <details>
                    <summary>What is the minimum order quantity?</summary>
                    <div class="faq_content">
                        <p>
                            Our minimum order quantities are designed to be beginner-friendly for student artists. 
                            Typically, we require:
                        </p>
                        <ul>
                            <li>Stickers: 50 units minimum</li>
                            <li>Prints: 10 units minimum</li>
                            <li>Button Pins: 25 units minimum</li>
                            <li>Custom Cut Items: 10 units minimum</li>
                        </ul>
                        <p>Contact us for special requests or bulk orders.</p>
                    </div>
                </details>
            </div>

            <div class="faq_item">
                <details>
                    <summary>What file formats do you accept for artwork?</summary>
                    <div class="faq_content">
                        <p>We accept the following file formats for your artwork:</p>
                        <ul>
                            <li><strong>PNG</strong> (recommended for transparent backgrounds)</li>
                            <li><strong>PSD</strong> (Photoshop)</li>
                            <li><strong>AI</strong> (Adobe Illustrator)</li>
                            <li><strong>PDF</strong> (high-quality prints)</li>
                        </ul>
                        <p><strong>Important:</strong> Please ensure your artwork is at least 300 DPI for the best print quality.</p>
                    </div>
                </details>
            </div>

            <div class="faq_item">
                <details>
                    <summary>How long does production take?</summary>
                    <div class="faq_content">
                        <p>
                            Our typical production timeline is <strong>5–7 business days</strong> from the time your order is confirmed 
                            and final files are approved. This includes:
                        </p>
                        <ul>
                            <li>File review and quality check (1–2 days)</li>
                            <li>Production and printing (3–4 days)</li>
                            <li>Quality assurance and packaging (1 day)</li>
                        </ul>
                        <p>Rush orders may be available upon request for an additional fee.</p>
                    </div>
                </details>
            </div>

            <h2 style="margin-top: 40px;">Shipping & Orders</h2>

            <div class="faq_item">
                <details>
                    <summary>Do you ship nationwide?</summary>
                    <div class="faq_content">
                        <p>
                            Yes, we ship nationwide from our location in <strong>Cainta, Rizal</strong>. We partner with reliable 
                            courier services to ensure your orders arrive safely and on time.
                        </p>
                        <ul>
                            <li><strong>Metro Manila & nearby areas:</strong> 2–3 business days (extra fee may apply)</li>
                            <li><strong>Provincial areas:</strong> 4–7 business days</li>
                        </ul>
                        <p>Shipping costs will be calculated based on your location and order weight. We'll provide a quote before finalizing your order.</p>
                    </div>
                </details>
            </div>

            <div class="faq_item">
                <details>
                    <summary>What payment methods do you accept?</summary>
                    <div class="faq_content">
                        <p>We accept multiple payment methods for your convenience:</p>
                        <ul>
                            <li>GCash (QR Code)</li>
                            <li>PayMaya (QR Code)</li>
                            <li>BPI Bank Transfer (QR Code)</li>
                        </ul>
                        <p>Payment must be completed before production begins. Proof of Payment or E-Receipt must be uploaded after payment has been made.</p>
                    </div>
                </details>
            </div>

            <div class="faq_item">
                <details>
                    <summary>How do I place an order?</summary>
                    <div class="faq_content">
                        <p>Placing an order with us is easy:</p>
                        <ol>
                            <li><strong>Browse our products</strong> — Visit our Products page to see available options</li>
                            <li><strong>Contact us</strong> — Reach out with your design requirements and product choice</li>
                            <li><strong>Submit your artwork</strong> — Provide your files in the accepted formats</li>
                            <li><strong>Review & approve</strong> — We'll send you a mock-up for approval</li>
                            <li><strong>Make payment</strong> — Complete payment to start production</li>
                            <li><strong>Receive your order</strong> — Your merchandise will be delivered to you</li>
                        </ol>
                    </div>
                </details>
            </div>

            <h2 style="margin-top: 40px;">Customization & Finishes</h2>

            <div class="faq_item">
                <details>
                    <summary>What lamination options are available for stickers?</summary>
                    <div class="faq_content">
                        <p>We offer a variety of lamination finishes to match your design aesthetic:</p>
                        <ul>
                            <li><strong>Matte</strong> — Non-shiny, elegant finish</li>
                            <li><strong>Glossy</strong> — Shiny, vibrant finish</li>
                            <li><strong>Glitter</strong> — Sparkly, eye-catching finish</li>
                            <li><strong>Holo Rainbow</strong> — Iridescent holographic effect</li>
                            <li><strong>Holo Broken Glass</strong> — Unique fragmented holographic effect</li>
                        </ul>
                        <p>Each finish can be combined with different sticker types (die-cut, kiss-cut) for maximum customization.</p>
                    </div>
                </details>
            </div>

            <div class="faq_item">
                <details>
                    <summary>Can I request a custom size or shape?</summary>
                    <div class="faq_content">
                        <p>
                            Absolutely! We pride ourselves on custom manufacturing. Whether you want a unique size or special shape, 
                            we can accommodate your vision. Custom cuts are available for:
                        </p>
                        <ul>
                            <li>Stickers with any custom outline</li>
                            <li>Prints in non-standard dimensions</li>
                            <li>Button pins in unique shapes</li>
                        </ul>
                        <p>Custom requests may have slightly longer production times and potentially different pricing. Contact us for a personalized quote.</p>
                    </div>
                </details>
            </div>

            <h2 style="margin-top: 40px;">Pricing & Discounts</h2>

            <div class="faq_item">
                <details>
                    <summary>Do you offer discounts for bulk orders?</summary>
                    <div class="faq_content">
                        <p>
                            Yes! We believe in supporting student artists with affordable pricing. Our bulk discount structure includes:
                        </p>
                        <ul>
                            <li><strong>Small orders (50–100 units):</strong> Base pricing</li>
                            <li><strong>Medium orders (101–250 units):</strong> 5–10% discount</li>
                            <li><strong>Large orders (251+ units):</strong> 10–15% discount</li>
                        </ul>
                        <p>For very large orders or special requests, contact us directly for a customized quote.</p>
                    </div>
                </details>
            </div>

            <div class="faq_item">
                <details>
                    <summary>Are there any hidden costs?</summary>
                    <div class="faq_content">
                        <p>
                            No! We believe in transparent pricing. Our quotes include:
                        </p>
                        <ul>
                            <li>Product manufacturing cost</li>
                            <li>Design review and file preparation</li>
                            <li>Shipping cost (calculated based on location)</li>
                        </ul>
                        <p>
                            <strong>Any additional costs</strong> (rush orders, custom designs, special finishes) will be clearly 
                            communicated before you confirm your order.
                        </p>
                    </div>
                </details>
            </div>

            <h2 style="margin-top: 40px;">Still Have Questions?</h2>
            <div class="faqs_contact_section">
                <p>Didn't find the answer you're looking for? <strong>Contact us directly!</strong></p>
                <p>
                    Location: <strong>Cainta, Rizal</strong><br>
                    We're here to help make your merchandise dreams a reality.
                </p>
            </div>
        </div>
    </div>

@endsection
