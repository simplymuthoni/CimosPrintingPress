<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Extract data
$projectType = $input['projectType'] ?? '';
$quantity = (int)($input['quantity'] ?? 0);
$paperSize = $input['paperSize'] ?? '';
$colorMode = $input['colorMode'] ?? '';
$services = $input['services'] ?? [];

// Price calculation function
function calculatePrice($projectType, $quantity, $paperSize, $colorMode, $services) {
    $basePrice = 0;
    $additionalServices = 0;
    
    // Base prices from the price list
    $priceTable = [
        // A4 Cash Sales
        'cash-sales' => [
            'bank' => [
                '6-12' => 350, '13-24' => 340, '25-48' => 330, '49-100' => 320
            ],
            'ncr' => [
                '6-12' => 400, '13-24' => 390, '25-48' => 380, '49-100' => 370
            ]
        ],
        'cash-sales-ncr' => [
            'bank' => [
                '6-12' => 350, '13-24' => 340, '25-48' => 330, '49-100' => 320
            ],
            'ncr' => [
                '6-12' => 400, '13-24' => 390, '25-48' => 380, '49-100' => 370
            ]
        ],
        
        // A5 Receipt/Invoice/Delivery
        'receipt' => [
            'bank' => [
                '6-12' => 500, '13-24' => 490, '25-48' => 480, '49-100' => 470
            ],
            'ncr' => [
                '6-12' => 420, '13-24' => 410, '25-48' => 400, '49-100' => 390
            ]
        ],
        'receipt-ncr' => [
            'bank' => [
                '6-12' => 500, '13-24' => 490, '25-48' => 480, '49-100' => 470
            ],
            'ncr' => [
                '6-12' => 420, '13-24' => 410, '25-48' => 400, '49-100' => 390
            ]
        ],
        'invoice' => [
            'bank' => [
                '6-12' => 500, '13-24' => 490, '25-48' => 480, '49-100' => 470
            ],
            'ncr' => [
                '6-12' => 420, '13-24' => 410, '25-48' => 400, '49-100' => 390
            ]
        ],
        'invoice-ncr' => [
            'bank' => [
                '6-12' => 500, '13-24' => 490, '25-48' => 480, '49-100' => 470
            ],
            'ncr' => [
                '6-12' => 420, '13-24' => 410, '25-48' => 400, '49-100' => 390
            ]
        ],
        'delivery-note' => [
            'bank' => [
                '6-12' => 500, '13-24' => 490, '25-48' => 480, '49-100' => 470
            ],
            'ncr' => [
                '6-12' => 420, '13-24' => 410, '25-48' => 400, '49-100' => 390
            ]
        ],
        'delivery-note-ncr' => [
            'bank' => [
                '6-12' => 500, '13-24' => 490, '25-48' => 480, '49-100' => 470
            ],
            'ncr' => [
                '6-12' => 420, '13-24' => 410, '25-48' => 400, '49-100' => 390
            ]
        ],
        
        // A6 Receipt/Invoice/Delivery
        'receipt-bank' => [
            'bank' => [
                '6-12' => 500, '13-24' => 490, '25-48' => 480, '49-100' => 470
            ],
            'ncr' => [
                '6-12' => 550, '13-24' => 540, '25-48' => 530, '49-100' => 530
            ]
        ],
        'receipt-ncr-a6' => [
            'bank' => [
                '6-12' => 500, '13-24' => 490, '25-48' => 480, '49-100' => 470
            ],
            'ncr' => [
                '6-12' => 550, '13-24' => 540, '25-48' => 530, '49-100' => 530
            ]
        ],
        'invoice-a6' => [
            'bank' => [
                '6-12' => 500, '13-24' => 490, '25-48' => 480, '49-100' => 470
            ],
            'ncr' => [
                '6-12' => 550, '13-24' => 540, '25-48' => 530, '49-100' => 530
            ]
        ],
        'invoice-ncr-a6' => [
            'bank' => [
                '6-12' => 500, '13-24' => 490, '25-48' => 480, '49-100' => 470
            ],
            'ncr' => [
                '6-12' => 550, '13-24' => 540, '25-48' => 530, '49-100' => 530
            ]
        ]
    ];
    
    // Additional service prices
    $servicePrices = [
        'platemaking' => 100,
        'printing' => 200,
        'bookbinding' => 50, // Base binding price
        'papercutting' => 10
    ];
    
    // Determine quantity range
    $quantityRange = '';
    if ($quantity >= 6 && $quantity <= 12) {
        $quantityRange = '6-12';
    } elseif ($quantity >= 13 && $quantity <= 24) {
        $quantityRange = '13-24';
    } elseif ($quantity >= 25 && $quantity <= 48) {
        $quantityRange = '25-48';
    } elseif ($quantity >= 49 && $quantity <= 100) {
        $quantityRange = '49-100';
    } else {
        // For quantities outside the standard range, use closest range
        if ($quantity < 6) {
            $quantityRange = '6-12';
        } else {
            $quantityRange = '49-100';
        }
    }
    
    // Determine paper type from project type
    $paperType = 'bank';
    if (strpos($projectType, 'ncr') !== false) {
        $paperType = 'ncr';
    }
    
    // Calculate base price
    if (isset($priceTable[$projectType][$paperType][$quantityRange])) {
        $basePrice = $priceTable[$projectType][$paperType][$quantityRange];
    } else {
        // Default pricing for custom projects
        $basePrice = 300; // Base price for custom projects
    }
    
    // Calculate additional services
    foreach ($services as $service) {
        if (isset($servicePrices[$service])) {
            $additionalServices += $servicePrices[$service];
        }
    }
    
    // Adjust binding price based on paper size
    if (in_array('bookbinding', $services)) {
        $bindingPrice = 50; // Default A5/A6
        if ($paperSize === 'a4') {
            $bindingPrice = strpos($projectType, '50x2') !== false ? 70 : 100;
        }
        $additionalServices += $bindingPrice - 50; // Adjust from base price
    }
    
    // Color mode adjustments
    $colorMultiplier = 1;
    if ($colorMode === 'full-color') {
        $colorMultiplier = 1.5; // 50% increase for full color
    } elseif ($colorMode === 'spot-color') {
        $colorMultiplier = 1.2; // 20% increase for spot color
    }
    
    $totalPrice = ($basePrice + $additionalServices) * $colorMultiplier;
    
    return [
        'basePrice' => $basePrice,
        'additionalServices' => $additionalServices,
        'colorMultiplier' => $colorMultiplier,
        'totalPrice' => round($totalPrice, 2)
    ];
}

// Validate required fields
if (empty($projectType) || $quantity <= 0) {
    echo json_encode([
        'error' => 'Project type and quantity are required',
        'success' => false
    ]);
    exit;
}

// Calculate price
$priceCalculation = calculatePrice($projectType, $quantity, $paperSize, $colorMode, $services);

// Prepare response
$response = [
    'success' => true,
    'projectType' => $projectType,
    'quantity' => $quantity,
    'paperSize' => $paperSize,
    'colorMode' => $colorMode,
    'services' => $services,
    'pricing' => $priceCalculation,
    'breakdown' => [
        'basePrice' => [
            'amount' => $priceCalculation['basePrice'],
            'description' => 'Base price for ' . str_replace('-', ' ', $projectType)
        ],
        'additionalServices' => [
            'amount' => $priceCalculation['additionalServices'],
            'description' => 'Additional services: ' . implode(', ', $services)
        ],
        'colorAdjustment' => [
            'multiplier' => $priceCalculation['colorMultiplier'],
            'description' => 'Color mode: ' . ($colorMode ?: 'Standard')
        ],
        'totalPrice' => [
            'amount' => $priceCalculation['totalPrice'],
            'currency' => 'KSH'
        ]
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>