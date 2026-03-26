<?php

namespace Database\Seeders;

use App\Models\FAQ;
use Illuminate\Database\Seeder;

class FAQSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            // General Questions
            [
                'category' => 'General Questions',
                'question' => 'What products does Scruffs&Chyrrs offer?',
                'answer' => 'We specialize in merchandise manufacturing for student artists and creators. Our services include:

• **Stickers** — Custom printed stickers with various finishes
• **Prints** — High-quality art prints and posters
• **Button Pins** — Custom-designed badges and pins
• **Custom Cut Items** — Uniquely shaped merchandise

All products are manufactured with attention to detail and quality at affordable prices.',
                'sort_order' => 1,
            ],
            [
                'category' => 'General Questions',
                'question' => 'What is the minimum order quantity?',
                'answer' => 'Our minimum order quantities are designed to be beginner-friendly for student artists. Typically, we require:

• Stickers: 50 units minimum
• Prints: 10 units minimum
• Button Pins: 25 units minimum
• Custom Cut Items: 10 units minimum

Contact us for special requests or bulk orders.',
                'sort_order' => 2,
            ],
            [
                'category' => 'General Questions',
                'question' => 'What file formats do you accept for artwork?',
                'answer' => 'We accept the following file formats for your artwork:

• **PNG** (recommended for transparent backgrounds)
• **PSD** (Photoshop)
• **AI** (Adobe Illustrator)
• **PDF** (high-quality prints)

**Important:** Please ensure your artwork is at least 300 DPI for the best print quality.',
                'sort_order' => 3,
            ],
            [
                'category' => 'General Questions',
                'question' => 'How long does production take?',
                'answer' => 'Our typical production timeline is **5–7 business days** from the time your order is confirmed and final files are approved. This includes:

• File review and quality check (1–2 days)
• Production and printing (3–4 days)
• Quality assurance and packaging (1 day)

Rush orders may be available upon request for an additional fee.',
                'sort_order' => 4,
            ],
            // Shipping & Orders
            [
                'category' => 'Shipping & Orders',
                'question' => 'Do you ship nationwide?',
                'answer' => 'Yes, we ship nationwide from our location in **Cainta, Rizal**. We partner with reliable courier services to ensure your orders arrive safely and on time.

• **Metro Manila & nearby areas:** 2–3 business days (extra fee may apply)
• **Provincial areas:** 4–7 business days

Shipping costs will be calculated based on your location and order weight. We\'ll provide a quote before finalizing your order.',
                'sort_order' => 5,
            ],
            [
                'category' => 'Shipping & Orders',
                'question' => 'What payment methods do you accept?',
                'answer' => 'We accept multiple payment methods for your convenience:

• GCash (QR Code)
• PayMaya (QR Code)
• BPI Bank Transfer (QR Code)

Payment must be completed before production begins. Proof of Payment or E-Receipt must be uploaded after payment has been made.',
                'sort_order' => 6,
            ],
            [
                'category' => 'Shipping & Orders',
                'question' => 'How do I place an order?',
                'answer' => 'Placing an order with us is easy:

1. **Browse our products** — Visit our Products page to see available options
2. **Contact us** — Reach out with your design requirements and product choice
3. **Submit your artwork** — Provide your files in the accepted formats
4. **Review & approve** — We\'ll send you a mock-up for approval
5. **Make payment** — Complete payment to start production
6. **Receive your order** — Your merchandise will be delivered to you',
                'sort_order' => 7,
            ],
            // Customization & Finishes
            [
                'category' => 'Customization & Finishes',
                'question' => 'What lamination options are available for stickers?',
                'answer' => 'We offer a variety of lamination finishes to match your design aesthetic:

• **Matte** — Non-shiny, elegant finish
• **Glossy** — Shiny, vibrant finish
• **Glitter** — Sparkly, eye-catching finish
• **Holo Rainbow** — Iridescent holographic effect
• **Holo Broken Glass** — Unique fragmented holographic effect

Each finish can be combined with different sticker types (die-cut, kiss-cut) for maximum customization.',
                'sort_order' => 8,
            ],
            [
                'category' => 'Customization & Finishes',
                'question' => 'Can I request a custom size or shape?',
                'answer' => 'Absolutely! We pride ourselves on custom manufacturing. Whether you want a unique size or special shape, we can accommodate your vision. Custom cuts are available for:

• Stickers with any custom outline
• Prints in non-standard dimensions
• Button pins in unique shapes

Custom requests may have slightly longer production times and potentially different pricing. Contact us for a personalized quote.',
                'sort_order' => 9,
            ],
            // Pricing & Discounts
            [
                'category' => 'Pricing & Discounts',
                'question' => 'Do you offer discounts for bulk orders?',
                'answer' => 'Yes! We believe in supporting student artists with affordable pricing. Our bulk discount structure includes:

• **Small orders (50–100 units):** Base pricing
• **Medium orders (101–250 units):** 5–10% discount
• **Large orders (251+ units):** 10–15% discount

For very large orders or special requests, contact us directly for a customized quote.',
                'sort_order' => 10,
            ],
            [
                'category' => 'Pricing & Discounts',
                'question' => 'Are there any hidden costs?',
                'answer' => 'No! We believe in transparent pricing. Our quotes include:

• Product manufacturing cost
• Design review and file preparation
• Shipping cost (calculated based on location)

**Any additional costs** (rush orders, custom designs, special finishes) will be clearly communicated before you confirm your order.',
                'sort_order' => 11,
            ],
        ];

        foreach ($faqs as $faq) {
            FAQ::updateOrCreate(
                ['category' => $faq['category'], 'question' => $faq['question']],
                $faq
            );
        }
    }
}
