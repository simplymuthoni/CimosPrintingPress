<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request (required for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'success' => false]);
    exit;
}

// Get JSON input with error handling
$rawInput = file_get_contents('php://input');
if ($rawInput === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Unable to read request body', 'success' => false]);
    exit;
}

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid JSON input: ' . json_last_error_msg(),
        'success' => false
    ]);
    exit;
}

// Extract data with defaults
$projectType = $input['projectType'] ?? '';
$quantity = (int)($input['quantity'] ?? 0);
$paperSize = $input['paperSize'] ?? '';
$colorMode = $input['colorMode'] ?? 'black-white';
$services = $input['services'] ?? [];

// Validate color mode
$validColorModes = ['full-color', 'spot-color', 'black-white'];
if (!in_array($colorMode, $validColorModes)) {
    $colorMode = 'black-white'; // Default to black-white if invalid
}

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
    switch ($colorMode) {
        case 'full-color':
            $colorMultiplier = 1.5; // 50% increase for full color (CMYK)
            break;
        case 'spot-color':
            $colorMultiplier = 1.2; // 20% increase for spot color
            break;
        case 'black-white':
        default:
            $colorMultiplier = 1; // No increase for black & white
            break;
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
if (empty($projectType)) {
    echo json_encode([
        'error' => 'Project type is required',
        'success' => false
    ]);
    exit;
}

if ($quantity <= 0) {
    echo json_encode([
        'error' => 'Quantity must be greater than 0',
        'success' => false
    ]);
    exit;
}

try {
    // Calculate price
    $priceCalculation = calculatePrice($projectType, $quantity, $paperSize, $colorMode, $services);
    
    // Color mode descriptions
    $colorModeDescriptions = [
        'full-color' => 'Full Color (CMYK)',
        'spot-color' => 'Spot Color',
        'black-white' => 'Black & White'
    ];
    
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
                'description' => 'Additional services: ' . (empty($services) ? 'None' : implode(', ', $services))
            ],
            'colorAdjustment' => [
                'multiplier' => $priceCalculation['colorMultiplier'],
                'description' => $colorModeDescriptions[$colorMode] ?? 'Standard'
            ],
            'totalPrice' => [
                'amount' => $priceCalculation['totalPrice'],
                'currency' => 'KSH'
            ]
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error: ' . $e->getMessage(),
        'success' => false
    ]);
}
?>