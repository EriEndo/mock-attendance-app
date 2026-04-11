<?php

return [
    'user' => [
        [
            'label' => '勤怠',
            'route' => 'attendance.index',
        ],
        [
            'label' => '勤怠一覧',
            'route' => 'attendance.list',
        ],
        [
            'label' => '申請',
            'route' => 'stamp_correction_request.list',
        ],
    ],

    'admin' => [
        [
            'label' => '勤怠一覧',
            'route' => 'admin.attendance.list',
        ],
        [
            'label' => 'スタッフ一覧',
            'route' => 'admin.staff.list',
        ],
        [
            'label' => '申請一覧',
            'route' => 'admin.stamp_correction_request.list',
        ],

    ],
];