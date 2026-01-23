<?php

namespace App\Helpers;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ConstantHelper
{
    const DZERO = 0;
    const PAGINATE = 10;

    const TIME_RANGES = [
        '1to6' => ['00:00', '05:59'],
        '6to9' => ['06:00', '08:59'],
        '9to12' => ['09:00', '11:59'],
        '12to15' => ['12:00', '14:59'],
        '15to18' => ['15:00', '17:59'],
        '18to21' => ['18:00', '20:59'],
        '21to1' => ['21:00', '23:59'],
    ];

    const DATE_TIME_FORMAT = "Y-m-d H:i:s";
    const SQL_DATE_TIME = "Y-m-d H:i:s";

    //ALERT STATUSES
    const ERROR = "error";
    const SUCCESS = "success";
    const WARNING = "warning";

    //MAX FILE SIZE SUPPORT
    const MAX_FILE_SIZE = "1024"; // KB

    //ORDER RELATED CONSTANT
    const INSP_TIME = 5; //mins
    const CLEANING_TIME = 5; //mins
    const QC_TIME = 5; //mins
    const GROUP_COMP_START_TIME = "00:00";
    const GROUP_COMP_END_TIME = "23:59";
    const DAY_END_TIME = "23:59";

    const LARGEST_JOB_FIRST_PREF = "largest_qty_first";
    const CUSTOMER_TIMELINE_PREF = "customer_timeline";
    const FCFS_PREF = "fcfs";
    const POURING_TIME = 20;
    const DEFAULT_PRIORITY = 9999;
    const DEFAULT_DATE_TIME = "2001-01-01 00:00:00";
    const TO_FROM_LOOP = [1,2];
    const LOADING_TIME = 10; //mins
    const SCHEDULE_GRAPH_SLOT_HOURS = 40;
    const TRAVEL_TIME = 30; //mins
    const BOOM_PUMP = "Boom Pump";
    const DATE_HOUR_ONLY_FORMAT = "Y-m-d h A";
    const COMPLETE_DATE_TIME_FORMAT = "Y-m-d H:i:s";
    const ONE_HOUR_SLOTS_NO = 6;
    const EXCEL_DATE_FORMAT = "d-m-Y H:i:s";
    const MAX_MINS_LOOP_CHECK = 60; //mins (approx 1 day)
    const DEFAULT_GEN_SCH_ORDER_SORT =
    [
        ['priority', 'asc'],
        ['delivery_time', 'asc']
    ];
    const LARGEST_JOB_GEN_SCH_ORDER_SORT =
    [
        ['priority', 'asc'],
        ['quantity', 'desc'],
        // ['order_start_time', 'asc'],
    ];

    const AVAILABILITY = "AVL";
    const NON_AVAILABILITY = "N_AVL";
    const SLOT_TIME_FORMAT = "h:i A";

    const TM_NOT_AVL = "Transit Mixer not available";
    const PUMP_NOT_AVL = "Pump not available";
    const TM_AND_PUMP_NOT_AVL = "Transit Mixer and Pump not available";
    const TRIP_GAP = "Trip Gap";
    const MAX_DELAY_IN_MINS = 360;

    const FOUR_HOUR_MINS = 240;
    const THREE_HOUR_MINS = 180;
    const TWO_HOUR_MINS = 120;
    const ONE_HOUR_MINS = 60;
    const ONE_DAY_MINS = 1440;
    const LIVE_MARKER_DEFAULT_MARGIN = 221; //in px
    const PER_MIN_MARKER_MARGIN = 1.5; //in px
    const ACTIVE = "Active";
    const INACTIVE = "Inactive";
    const ROW_STATUSES = [self::ACTIVE, self::INACTIVE];
    const CASH_TYPE_CUST = "Cash";
    const CREDIT_TYPE_CUST = "Credit";
    const CUST_TYPES = [self::CASH_TYPE_CUST, self::CREDIT_TYPE_CUST];
    const RESIDENTIAL_PROJECT = "Residential";
    const COMMERCIAL_PROJECT = "Commercial";
    const PROJECT_TYPES = [self::RESIDENTIAL_PROJECT, self::COMMERCIAL_PROJECT];
    const HTTP_SUCCESS = 200;
    const HTTP_VALIDATION_ERROR = 422;
    const HTTP_UNAUTH = 401;
    const HTTP_SERVER_ERROR = 500;
    const USER_TYPE_ADMIN = 'Admin';
    const USER_TYPE_CUST = 'Customer';
    const USER_TYPE_DRIVER = 'Driver';
    const USER_TYPES = [self::USER_TYPE_ADMIN, self::USER_TYPE_CUST, self::USER_TYPE_DRIVER];
    const UPCOMING_ORDERS = 'upcoming';
    const PAST_ORDERS = 'past';
    const LIVE_ORDERS = 'live';
    const ORDER_FILTERS = [self::UPCOMING_ORDERS, self::PAST_ORDERS, self::LIVE_ORDERS];
    const MOBILE_ACCESS_TYPE = 'mobile';
    const WEB_ACCESS_TYPE = 'web';
    const APP_TOKEN_NAME = "authAppToken";
    const OTP_EXPIRY_TIME_IN_MINS = 10;
    const RESET_PASSWORD_SINGLE_TYPE_FOR_OTP = "reset_password_single";
    const RESET_PASSWORD_MULTIPLE_TYPE_FOR_OTP = "reset_password_multiple";
    const PENDING_ORDER = "Pending";
    const ORDER_APPROVAL_TRANSACTION = "order_approval";
    const APPROVAL_TYPE_HORIZONTAL = "Horizontal";
    const APPROVAL_TYPE_VERTICAL = "Vertical";
    const APPROVAL_TYPES = [self::APPROVAL_TYPE_HORIZONTAL, self::APPROVAL_TYPE_VERTICAL];
    const APPROVAL_LEVEL_ALL = "All";
    const APPROVAL_LEVEL_ANY = "Any";
    const APPROVAL_LEVEL_TYPES = [self::APPROVAL_LEVEL_ALL, self::APPROVAL_LEVEL_ANY];
    const NOT_REQUIRED_LABEL = "Not Required";
    const CUST_CONFIRMATION_DOC_COLLECTION_NAME = "cust_confirmation_documents";
    const PROFILE_COLLECTION_NAME_SPATIE = 'profile_image';
    const BATCHING = "Batching";
    const INTERNAL_QC = "Internal QC";
    const ON_SITE_TRAVEL = "On Site Travel";
    const ON_SITE_INSP = "On Site Inspection";
    const POURING = "Pouring";
    const CLEAN_ON_SITE = "Cleaning at Site";
    const RETURN = "Return to Facility";
    const BATCHING_LIVE_KEY = "loading";
    const INTERNAL_QC_LIVE_KEY = "qc";
    const ON_SITE_TRAVEL_LIVE_KEY = "travel";
    const ON_SITE_INSP_LIVE_KEY = "insp";
    const POURING_LIVE_KEY = "pouring";
    const CLEAN_ON_SITE_LIVE_KEY = "cleaning";
    const RETURN_LIVE_KEY = "return";
    const DRIVER_SUB_TYPE_PUMP = "operator";
    const DRIVER_SUB_TYPE_TRUCK= "driver";

    const TRIP_ACTIVITIES = [
        self::BATCHING, self::INTERNAL_QC, self::ON_SITE_TRAVEL, self::ON_SITE_INSP, self::POURING, self::CLEAN_ON_SITE, self::RETURN
    ];

    const LIVE_TRIP_ACTIVITIES = [
        [
            'name' => self::BATCHING,
            'key' => self::BATCHING_LIVE_KEY,
            'report' => true,
            'reject' => false,
            'map' => false,
        ],
        [
            'name' => self::INTERNAL_QC,
            'key' => self::INTERNAL_QC_LIVE_KEY,
            'report' => false,
            'reject' => true,
            'map' => false,
        ],
        [
            'name' => self::ON_SITE_TRAVEL,
            'key' => self::ON_SITE_TRAVEL_LIVE_KEY,
            'report' => true,
            'reject' => false,
            'map' => true,
        ],
        [
            'name' => self::ON_SITE_INSP,
            'key' => self::ON_SITE_INSP_LIVE_KEY,
            'report' => false,
            'reject' => true,
            'map' => false,
        ],
        [
            'name' => self::POURING,
            'key' => self::POURING_LIVE_KEY,
            'report' => true,
            'reject' => false,
            'map' => false,
        ],
        [
            'name' => self::CLEAN_ON_SITE,
            'key' => self::CLEAN_ON_SITE_LIVE_KEY,
            'report' => true,
            'reject' => false,
            'map' => false,
        ],
        [
            'name' => self::RETURN,
            'key' => self::RETURN_LIVE_KEY,
            'report' => true,
            'reject' => false,
            'map' => true,
        ]
    ];
    const LIVE_PUMP_ACTIVITIES = [


        [
            'name' => self::ON_SITE_TRAVEL,
            'key' => self::ON_SITE_TRAVEL_LIVE_KEY,
            'report' => true,
            'reject' => false,
            'map' => true,
        ],

        [
            'name' => self::POURING,
            'key' => self::POURING_LIVE_KEY,
            'report' => true,
            'reject' => false,
            'map' => false,
        ],

        [
            'name' => self::RETURN,
            'key' => self::RETURN_LIVE_KEY,
            'report' => true,
            'reject' => false,
            'map' => true,
        ]
    ];

    const TRANSIT_MIXER_IMG_COLLECTION = 'transit_mixer_image';
    const CUST_PROJECT_IMG_COLLECTION = 'cust_project_image';
    const TRIP_REPORT_IMG_COLLECTION = 'trip_report_images';
    const TRIP_REJECT_IMG_COLLECTION = 'trip_reject_images';
    const CHAT_USER_COLORS = ['#BFB5FF', '#FCDD8C', '#FFBCD6'];

    const DRIVER_LOCATIONS_FIRESTORE_COLLECTION = "driver_locations";
    const DRIVER_LOCATIONS_FIRESTORE_DOCUMENT = "orderId_";
    const DRIVER_LOCATIONS_DRIVER_FIRESTORE_COLLECTION = "drivers";
    const USER_DEFAULT_TIMEZONE = 'Asia/Kolkata';
    const LIVE_ORDER_TRIPS_FIRESTORE_COLLECTION = "live_orders";
    const ACCESS_LEVEL_SYSTEM = "system";
    const ACCESS_LEVEL_USER = "custom";
    const ROLE_ACCESS_LEVELS = [self::ACCESS_LEVEL_SYSTEM, self::ACCESS_LEVEL_USER];
    const TRACK_ORDER_FIREBASE_COLLECTION = "driver_locations";
    const TRACK_ORDER_FIREBASE_DOCUMENT = "orderId_";
    const MOBILE_BOTTOM_BAR_HOME = "home";
    const MOBILE_BOTTOM_BAR_CHAT = "chat";
    const MOBILE_BOTTOM_BAR_CART = "cart";
    const MOBILE_BOTTOM_BAR_ORDER = "order";
    const MOBILE_BOTTOM_BAR_PROFILE = "profile";
    const DEFAULT_MOBILE_BOTTOM_BAR = [
        self::MOBILE_BOTTOM_BAR_HOME => 0,
        self::MOBILE_BOTTOM_BAR_CHAT => 0,
        self::MOBILE_BOTTOM_BAR_CART => 0,
        self::MOBILE_BOTTOM_BAR_ORDER => 0,
        self::MOBILE_BOTTOM_BAR_PROFILE => 1,
    ];
    const COMPLETE_MOBILE_BOTTOM_BAR = [
        self::MOBILE_BOTTOM_BAR_HOME => 1,
        self::MOBILE_BOTTOM_BAR_CHAT => 1,
        self::MOBILE_BOTTOM_BAR_CART => 1,
        self::MOBILE_BOTTOM_BAR_ORDER => 1,
        self::MOBILE_BOTTOM_BAR_PROFILE => 1,
    ];
    const ADMIN_DEFAULT_ACCESS_RIGHT_OBJECT = [
        'order_create' => 1,
        'order_view' => 1,
        'order_edit' => 1,
        'order_cancel' => 1,
        'chat' => 1
    ];
    const DEFULT_HASH_ALGO_STRING = 'sha256';
    const MAX_DISTANCE = 12764221;
    const TIME_SLOTS = [
        [
            "default_name" => "12 AM",
            "start_time" => "00:00:00",
            "end_time" => "01:00:00"
        ],
        [
            "default_name" => "01 AM",
            "start_time" => "01:00:00",
            "end_time" => "02:00:00"
        ],
        [
            "default_name" => "02 AM",
            "start_time" => "02:00:00",
            "end_time" => "03:00:00"
        ],
        [
            "default_name" => "03 AM",
            "start_time" => "03:00:00",
            "end_time" => "04:00:00"
        ],
        [
            "default_name" => "04 AM",
            "start_time" => "04:00:00",
            "end_time" => "05:00:00"
        ],
        [
            "default_name" => "05 AM",
            "start_time" => "05:00:00",
            "end_time" => "06:00:00"
        ],
        [
            "default_name" => "06 AM",
            "start_time" => "06:00:00",
            "end_time" => "07:00:00"
        ],
        [
            "default_name" => "07 AM",
            "start_time" => "07:00:00",
            "end_time" => "08:00:00"
        ],
        [
            "default_name" => "08 AM",
            "start_time" => "08:00:00",
            "end_time" => "09:00:00"
        ],
        [
            "default_name" => "09 AM",
            "start_time" => "09:00:00",
            "end_time" => "10:00:00"
        ],
        [
            "default_name" => "10 AM",
            "start_time" => "10:00:00",
            "end_time" => "11:00:00"
        ],
        [
            "default_name" => "11 AM",
            "start_time" => "11:00:00",
            "end_time" => "12:00:00"
        ],
        [
            "default_name" => "12 PM",
            "start_time" => "12:00:00",
            "end_time" => "13:00:00"
        ],
        [
            "default_name" => "01 PM",
            "start_time" => "13:00:00",
            "end_time" => "14:00:00"
        ],
        [
            "default_name" => "02 PM",
            "start_time" => "14:00:00",
            "end_time" => "15:00:00"
        ],
        [
            "default_name" => "03 PM",
            "start_time" => "15:00:00",
            "end_time" => "16:00:00"
        ],
        [
            "default_name" => "04 PM",
            "start_time" => "16:00:00",
            "end_time" => "07:00:00"
        ],
        [
            "default_name" => "05 PM",
            "start_time" => "17:00:00",
            "end_time" => "18:00:00"
        ],
        [
            "default_name" => "06 PM",
            "start_time" => "18:00:00",
            "end_time" => "09:00:00"
        ],
        [
            "default_name" => "07 PM",
            "start_time" => "19:00:00",
            "end_time" => "20:00:00"
        ],
        [
            "default_name" => "08 PM",
            "start_time" => "20:00:00",
            "end_time" => "21:00:00"
        ],
        [
            "default_name" => "09 PM",
            "start_time" => "21:00:00",
            "end_time" => "22:00:00"
        ],
        [
            "default_name" => "10 PM",
            "start_time" => "22:00:00",
            "end_time" => "23:00:00"
        ],
        [
            "default_name" => "11 PM",
            "start_time" => "23:00:00",
            "end_time" => "23:59:00"
        ],
    ];
    const SLOT_AVL = "avl";
    const SLOT_FILLING_FAST = "red";
    const SLOT_BOOKED = "booked";
}
