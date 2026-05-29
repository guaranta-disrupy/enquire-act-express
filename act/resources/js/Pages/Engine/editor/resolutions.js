export const DESKTOP_RESOLUTIONS = [
    // Large/Maximized Desktop,1920 × 1080,16:9,0.563
    // Standard Desktop,1440 × 900,8:5,0.625,
    // Small Desktop/Laptop,1280 × 800,8:5,0.625
    {
        name: "1920x1080",
        width: 1920,
        height: 1080,
        aspectRatio: "16:9",
        decimal: 0.563,
    },
    {
        name: "1440x900",
        width: 1440,
        height: 900,
        aspectRatio: "8:5",
        decimal: 0.625,
    },
    {
        name: "1280x800",
        width: 1280,
        height: 800,
        aspectRatio: "8:5",
        decimal: 0.625,
    },
];

export const TABLET_RESOLUTIONS = [
    // Portrait-oriented logical viewports

    // iPad Pro 12.9-inch,Largest,1024 × 1366,3:4,1.333
    // iPad 10.2-inch (Standard),Medium,810 × 1080,3:4,1.333
    // iPad Mini (6th Gen),Smallest,744 × 1133,1.52:1,1.523

    {
        name: 'iPad Pro 12.9" (1024×1366)',
        width: 1024,
        height: 1366,
        aspectRatio: "3:4",
        decimal: 1.333,
    },
    {
        name: 'iPad (10th Generation) 10.9"',
        width: 820,
        height: 1180,
        aspectRatio: "41:59",
        decimal: 1.439,
    },
    {
        name: "iPad Mini (6th Gen) (744×1133)",
        width: 744,
        height: 1133,
        aspectRatio: "1.52:1",
        decimal: 1.523,
    },
    // Galaxy Tab A 8.0,Smallest,800 × 1280,5:8,1.600
    // Galaxy Tab S9 (Standard),Medium,832 × 1334,16:10,1.603
    // Galaxy Tab S9 Ultra,Largest,960 × 1536,16:10,1.600

    {
        name: "Galaxy Tab S9 Ultra (960×1536)",
        width: 960,
        height: 1536,
        aspectRatio: "16:10",
        decimal: 1.6,
    },
    {
        name: "Galaxy Tab S9 (832×1334)",
        width: 832,
        height: 1334,
        aspectRatio: "16:10",
        decimal: 1.603,
    },
    {
        name: "Galaxy Tab A 8.0 (800×1280)",
        width: 800,
        height: 1280,
        aspectRatio: "5:8",
        decimal: 1.6,
    },
];

export const MOBILE_RESOLUTIONS = [
    // iPhone Models
    {
        name: "iPhone 16/17 Pro Max",
        width: 440,
        height: 956,
        aspectRatio: "19.5:42",
        decimal: 2.173,
    },
    {
        name: "iPhone 14/15 Pro Max",
        width: 430,
        height: 932,
        aspectRatio: "17.58:38.25",
        decimal: 2.167,
    },
    {
        name: "iPhone 14/15 Pro",
        width: 393,
        height: 852,
        aspectRatio: "19.5:9",
        decimal: 2.17,
    },
    {
        name: "iPhone 14/15 Plus",
        width: 428,
        height: 926,
        aspectRatio: "19.5:9",
        decimal: 2.16,
    },
    {
        name: "iPhone 12/13/14",
        width: 390,
        height: 844,
        aspectRatio: "15:32.46",
        decimal: 2.164,
    },
    {
        name: "iPhone 11",
        width: 414,
        height: 896,
        aspectRatio: "19.5:9",
        decimal: 2.16,
    },
    {
        name: "iPhone XR",
        width: 414,
        height: 896,
        aspectRatio: "19.5:9",
        decimal: 2.16,
    },
    {
        name: "iPhone SE (2nd/3rd)",
        width: 375,
        height: 667,
        aspectRatio: "9:16",
        decimal: 1.78,
    },
    {
        name: "iPhone 8",
        width: 375,
        height: 667,
        aspectRatio: "16:9",
        decimal: 1.78,
    },
    {
        name: "iPhone 7",
        width: 375,
        height: 667,
        aspectRatio: "16:9",
        decimal: 1.78,
    },
    {
        name: "iPhone 6s Plus",
        width: 414,
        height: 736,
        aspectRatio: "16:9",
        decimal: 1.78,
    },
    {
        name: "iPhone 6s",
        width: 375,
        height: 667,
        aspectRatio: "16:9",
        decimal: 1.78,
    },
    {
        name: "iPhone 6 Plus",
        width: 414,
        height: 736,
        aspectRatio: "16:9",
        decimal: 1.78,
    },
    {
        name: "iPhone 6",
        width: 375,
        height: 667,
        aspectRatio: "16:9",
        decimal: 1.78,
    },
    {
        name: "iPhone 5s",
        width: 320,
        height: 568,
        aspectRatio: "16:9",
        decimal: 1.78,
    },
    {
        name: "iPhone 5c",
        width: 320,
        height: 568,
        aspectRatio: "16:9",
        decimal: 1.78,
    },
    {
        name: "iPhone 5",
        width: 320,
        height: 568,
        aspectRatio: "16:9",
        decimal: 1.78,
    },
    {
        name: "iPhone 4s",
        width: 320,
        height: 480,
        aspectRatio: "3:2",
        decimal: 1.5,
    },
    {
        name: "iPhone 4",
        width: 320,
        height: 480,
        aspectRatio: "3:2",
        decimal: 1.5,
    },

    // Samsung Galaxy Models
    {
        name: "Samsung Galaxy S24 Ultra", // largest
        width: 412,
        height: 915,
        aspectRatio: "19.5:9",
        decimal: 2.22,
    },
    {
        name: "Samsung Galaxy A54", // medium
        width: 384,
        height: 854,
        aspectRatio: "19.5:9",
        decimal: 2.22,
    },
    {
        name: "Samsung Galaxy A24", // smallest
        width: 360,
        height: 640,
        aspectRatio: "9:16",
        decimal: 2.22,
    },

    // Google Pixel Models
    {
        name: "Pixel 7",
        width: 412,
        height: 915,
        aspectRatio: "~19.5:9",
        decimal: 2.22,
    },
    {
        name: "Pixel 6a",
        width: 393,
        height: 851,
        aspectRatio: "19.5:9",
        decimal: 2.16,
    },
    {
        name: "Pixel 5a",
        width: 393,
        height: 851,
        aspectRatio: "19.5:9",
        decimal: 2.16,
    },
    {
        name: "Huawei P30",
        width: 360,
        height: 780,
        aspectRatio: "19.5:9",
        decimal: 2.17,
    },
    {
        name: "Pixel 4a",
        width: 393,
        height: 851,
        aspectRatio: "19.5:9",
        decimal: 2.16,
    },
    {
        name: "Pixel 3a",
        width: 393,
        height: 786,
        aspectRatio: "18.5:9",
        decimal: 2.0,
    },

    // OnePlus Models
    {
        name: "OnePlus 9",
        width: 412,
        height: 915,
        aspectRatio: "20:9",
        decimal: 2.22,
    },
    {
        name: "OnePlus ACE",
        width: 360,
        height: 780,
        aspectRatio: "20:9",
        decimal: 2.22,
    },
];
