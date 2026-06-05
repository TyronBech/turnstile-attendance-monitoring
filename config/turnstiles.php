<?php

return [
    'input_mode' => env('RFID_INPUT_MODE', 'both'),

    'http' => [
        'enabled' => in_array(env('RFID_INPUT_MODE', 'both'), ['http', 'both'], true),
    ],

    'tcp' => [
        'enabled' => in_array(env('RFID_INPUT_MODE', 'both'), ['tcp', 'both'], true),
        'host' => env('RFID_TCP_HOST', '0.0.0.0'),
        'port' => env('RFID_TCP_PORT', 9000),
    ],

    'gates' => [
        'G01' => [
            'type' => env('GATE_01_TYPE', 'IN_OUT'),
            'group' => env('GATE_01_GROUP', 'MON-01'),
            'devices' => [
                'in_reader' => ['ip' => env('GATE_01_IN_READER_IP'), 'direction' => 'IN'],
                'out_reader' => ['ip' => env('GATE_01_OUT_READER_IP'), 'direction' => 'OUT'],
                'controller' => ['ip' => env('GATE_01_CONTROLLER_IP'), 'direction' => null],
            ],
        ],
        'G02' => [
            'type' => env('GATE_02_TYPE', 'IN_OUT'),
            'group' => env('GATE_02_GROUP', 'MON-01'),
            'devices' => [
                'in_reader' => ['ip' => env('GATE_02_IN_READER_IP'), 'direction' => 'IN'],
                'out_reader' => ['ip' => env('GATE_02_OUT_READER_IP'), 'direction' => 'OUT'],
                'controller' => ['ip' => env('GATE_02_CONTROLLER_IP'), 'direction' => null],
            ],
        ],
        'G03' => [
            'type' => env('GATE_03_TYPE', 'IN_OUT'),
            'group' => env('GATE_03_GROUP', 'MON-02'),
            'devices' => [
                'in_reader' => ['ip' => env('GATE_03_IN_READER_IP'), 'direction' => 'IN'],
                'out_reader' => ['ip' => env('GATE_03_OUT_READER_IP'), 'direction' => 'OUT'],
                'controller' => ['ip' => env('GATE_03_CONTROLLER_IP'), 'direction' => null],
            ],
        ],
        'G04' => [
            'type' => env('GATE_04_TYPE', 'IN_OUT'),
            'group' => env('GATE_04_GROUP', 'MON-02'),
            'devices' => [
                'in_reader' => ['ip' => env('GATE_04_IN_READER_IP'), 'direction' => 'IN'],
                'out_reader' => ['ip' => env('GATE_04_OUT_READER_IP'), 'direction' => 'OUT'],
                'controller' => ['ip' => env('GATE_04_CONTROLLER_IP'), 'direction' => null],
            ],
        ],
        'G05' => [
            'type' => env('GATE_05_TYPE', 'IN_ONLY'),
            'group' => env('GATE_05_GROUP', 'MON-03'),
            'devices' => [
                'in_reader' => ['ip' => env('GATE_05_IN_READER_IP'), 'direction' => 'IN'],
                'out_reader' => ['ip' => env('GATE_05_OUT_READER_IP'), 'direction' => 'OUT'],
                'controller' => ['ip' => env('GATE_05_CONTROLLER_IP'), 'direction' => null],
            ],
        ],
        'G06' => [
            'type' => env('GATE_06_TYPE', 'IN_ONLY'),
            'group' => env('GATE_06_GROUP', 'MON-03'),
            'devices' => [
                'in_reader' => ['ip' => env('GATE_06_IN_READER_IP'), 'direction' => 'IN'],
                'out_reader' => ['ip' => env('GATE_06_OUT_READER_IP'), 'direction' => 'OUT'],
                'controller' => ['ip' => env('GATE_06_CONTROLLER_IP'), 'direction' => null],
            ],
        ],
        'G07' => [
            'type' => env('GATE_07_TYPE', 'IN_ONLY'),
            'group' => env('GATE_07_GROUP', 'MON-03'),
            'devices' => [
                'in_reader' => ['ip' => env('GATE_07_IN_READER_IP'), 'direction' => 'IN'],
                'out_reader' => ['ip' => env('GATE_07_OUT_READER_IP'), 'direction' => 'OUT'],
                'controller' => ['ip' => env('GATE_07_CONTROLLER_IP'), 'direction' => null],
            ],
        ],
        'G08' => [
            'type' => env('GATE_08_TYPE', 'IN_ONLY'),
            'group' => env('GATE_08_GROUP', 'MON-03'),
            'devices' => [
                'in_reader' => ['ip' => env('GATE_08_IN_READER_IP'), 'direction' => 'IN'],
                'out_reader' => ['ip' => env('GATE_08_OUT_READER_IP'), 'direction' => 'OUT'],
                'controller' => ['ip' => env('GATE_08_CONTROLLER_IP'), 'direction' => null],
            ],
        ],
    ],
];
