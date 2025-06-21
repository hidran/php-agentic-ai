<?php
require __DIR__.'/../vendor/autoload.php';
use Dotenv\Dotenv;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\OpenAIConfig;
use Qdrant\Qdrant;
use App\QdrantRepository;

Dotenv::createImmutable(__DIR__.'/..')->load();

$config = new OpenAIConfig();
$config->apiKey = $_ENV['OPENAI_API_KEY'];
if (isset($_ENV['OPENAI_BASE_URL'])) {
    $config->url = $_ENV['OPENAI_BASE_URL'];
}

$embedder = new OpenAI3SmallEmbeddingGenerator($config);

$qdrant = new Qdrant(
    (new \Qdrant\Http\Builder())->build(new \Qdrant\Config($_ENV['QDRANT_URL']))
);

$repo = new QdrantRepository($qdrant);
$repo->setup($embedder->getEmbeddingLength()); // auto-detect dim

$faqs = [
    1 => [
        'question' => 'How can I reset my password?',
        'answer' => 'To reset your password, click "Forgot password?" on the login page and follow the instructions sent to your email.'
    ],
    2 => [
        'question' => 'Where can I download my invoice?',
        'answer' => 'You can download invoices in your account dashboard under the "Billing" section.'
    ],
    3 => [
        'question' => 'What payment methods do you accept?',
        'answer' => 'We accept Visa, Mastercard, American Express, PayPal, and Apple Pay.'
    ],
    4 => [
        'question' => 'How long does shipping take?',
        'answer' => 'Standard shipping takes 3-5 business days. Expedited shipping options are available at checkout.'
    ],
    5 => [
        'question' => 'Do you offer free shipping?',
        'answer' => 'Yes, we offer free shipping on orders over $50.'
    ],
    6 => [
        'question' => 'Can I track my order online?',
        'answer' => 'Yes, after your order ships you will receive a tracking number by email and can check status in your account.'
    ],
    7 => [
        'question' => 'What is your return policy?',
        'answer' => 'We accept returns within 30 days of purchase. Please visit our Returns page for detailed instructions.'
    ],
    8 => [
        'question' => 'How do I contact customer support?',
        'answer' => 'You can reach customer support by emailing support@example.com or calling our toll-free number.'
    ],
    9 => [
        'question' => 'Do you ship internationally?',
        'answer' => 'Yes, we ship to most countries worldwide. Shipping times and fees vary by location.'
    ],
    10 => [
        'question' => 'Is my payment information secure?',
        'answer' => 'We use SSL encryption and PCI-compliant processing to keep your payment details safe.'
    ],
    11 => [
        'question' => 'Can I cancel my order after placing it?',
        'answer' => 'You can cancel your order within 1 hour of placing it by visiting your order history page.'
    ],
    12 => [
        'question' => 'When will my preorder arrive?',
        'answer' => 'Preorders ship as soon as the product is released. Estimated dates are listed on the product page.'
    ],
    13 => [
        'question' => 'Do you offer gift cards?',
        'answer' => 'Yes, you can purchase digital gift cards in any amount on our Gift Card page.'
    ],
    14 => [
        'question' => 'Can I combine multiple orders for shipping?',
        'answer' => 'If the orders have not shipped yet, please contact support and we will do our best to combine them.'
    ],
    15 => [
        'question' => 'What should I do if my package is lost?',
        'answer' => 'If your package is lost, please contact support within 7 days of the expected delivery date.'
    ],
    16 => [
        'question' => 'Are returns free for international orders?',
        'answer' => 'At this time, return shipping for international orders is not covered.'
    ],
    17 => [
        'question' => 'How do I update my account information?',
        'answer' => 'Login and go to "Account Settings" to update your email, password, or address.'
    ],
    18 => [
        'question' => 'Can I pay with Google Pay?',
        'answer' => 'Yes, Google Pay is supported for eligible devices and browsers.'
    ],
    19 => [
        'question' => 'Do you offer a product warranty?',
        'answer' => 'All products come with a one-year limited warranty against defects.'
    ],
    20 => [
        'question' => 'Can I return a personalized item?',
        'answer' => 'Personalized items can only be returned if there is a manufacturing error.'
    ],
    21 => [
        'question' => 'How do I subscribe or unsubscribe from emails?',
        'answer' => 'You can manage your email preferences in your account settings or via the link in any email.'
    ],
    22 => [
        'question' => 'Where do I apply my discount code?',
        'answer' => 'Enter your discount code at checkout in the "Promo Code" field.'
    ],
    23 => [
        'question' => 'Do you have a loyalty program?',
        'answer' => 'Yes, earn points for every purchase and redeem them for discounts through our Rewards Program.'
    ],
    24 => [
        'question' => 'How do I submit a product review?',
        'answer' => 'After your purchase, you will receive an email invitation to review your product.'
    ],
    25 => [
        'question' => 'Can I change my shipping address after ordering?',
        'answer' => 'Contact support as soon as possible. If your order hasn’t shipped, we can update the address.'
    ],
    26 => [
        'question' => 'How can I get a copy of my receipt?',
        'answer' => 'Receipts are sent via email and available for download from your account order history.'
    ],
    27 => [
        'question' => 'What are your customer support hours?',
        'answer' => 'Customer support is available Monday to Friday, 9am–6pm local time.'
    ],
    28 => [
        'question' => 'How do I redeem a gift card?',
        'answer' => 'Enter your gift card code during checkout in the "Gift Card" field.'
    ],
    29 => [
        'question' => 'Can I request a specific delivery date?',
        'answer' => 'We currently do not support scheduled deliveries, but you may leave notes for the courier.'
    ],
    30 => [
        'question' => 'Where can I find product manuals?',
        'answer' => 'Product manuals can be downloaded from each product’s page in the "Documentation" tab.'
    ],
];

foreach ($faqs as $id => $entry) {
    $vector = $embedder->embedText($entry['question']); // Use your interface
    $repo->upsert($id, $vector, [
        'question' => $entry['question'],
        'answer'   => $entry['answer'],
    ]);
}
