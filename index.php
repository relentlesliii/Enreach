<?php
// ==============================================================================
// 1. SUPABASE CONFIGURATION & HELPER CLASS
// ==============================================================================
define('SUPABASE_URL', 'https://gksujwluesfczxswpuid.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_Wzwfct6E5NU2iGPIoru80w_s_i6Tl7v');

class Supabase {
    private static function request($endpoint,$method = 'GET', $data = null,$extraHeaders = []) {
        $url = SUPABASE_URL . '/rest/v1/' .$endpoint;
        $ch = curl_init($url);
        
        $headers = array_merge([
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ], $extraHeaders);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,$method);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $httpCode, 'data' => json_decode($response, true)];
    }

    public static function select($table,$query = '') {
        return self::request($table . ($query ? '?' . $query : ''));
    }

    public static function insert($table,$data) {
        return self::request($table, 'POST',$data);
    }

    public static function rpc($functionName,$data) {
        $url = SUPABASE_URL . '/rest/v1/rpc/' .$functionName;
        $ch = curl_init($url);$headers = [
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}

// ==============================================================================
// 2. KING JAMES VERSION (KJV) BIBLE VERSES
// ==============================================================================
$kjv_verses = [
    ["ref" => "John 3:16", "text" => "For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life."],
    ["ref" => "Psalm 23:1", "text" => "The LORD is my shepherd; I shall not want."],
    ["ref" => "Philippians 4:13", "text" => "I can do all things through Christ which strengtheneth me."],
    ["ref" => "Proverbs 3:5", "text" => "Trust in the LORD with all thine heart; and lean not unto thine own understanding."],
    ["ref" => "Isaiah 40:31", "text" => "But they that wait upon the LORD shall renew their strength; they shall mount up with wings as eagles; they shall run, and not be weary; and they shall walk, and not faint."],
    ["ref" => "Jeremiah 29:11", "text" => "For I know the thoughts that I think toward you, saith the LORD, thoughts of peace, and not of evil, to give you an expected end."],
    ["ref" => "Joshua 1:9", "text" => "Have not I commanded thee? Be strong and of a good courage; be not afraid, neither be thou dismayed: for the LORD thy God is with thee whithersoever thou goest."],
    ["ref" => "Matthew 6:33", "text" => "But seek ye first the kingdom of God, and his righteousness; and all these things shall be added unto you."],
    ["ref" => "Psalm 46:10", "text" => "Be still, and know that I am God: I will be exalted among the heathen, I will be exalted in the earth."],
    ["ref" => "Romans 12:2", "text" => "And be not conformed to this world: but be ye transformed by the renewing of your mind, that ye may prove what is that good, and acceptable, and perfect, will of God."]
];

// ==============================================================================
// 3. API ENDPOINTS HANDLER
// ==============================================================================
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $action =$_GET['api'];

    if ($action === 'get_data') {
        $categoriesRes = Supabase::select('prayer_categories', 'select=category_name,region_code,prayer_count&order=prayer_count.desc');$logsRes = Supabase::select('prayer_logs', 'select=selected_fields,region_code,created_at&order=id.desc&limit=10');

        $categories =$categoriesRes['data'] ?? [];
        $recent_logs =$logsRes['data'] ?? [];

        // Aggregate Region Totals from fetched logs or categories
        $region_totals = [];
        if (!empty($categories)) {
            foreach ($categories as$cat) {
                $code =$cat['region_code'];
                if (!isset($region_totals[$code])) {$region_totals[$code] = 0;
                }$region_totals[$code] += (int)$cat['prayer_count'];
            }
        }

        $formatted_totals = [];
        foreach ($region_totals as$code => $count) {$formatted_totals[] = ['region_code' => $code, 'count' =>$count];
        }

        echo json_encode([
            'success' => true,
            'categories' => $categories,
            'recent_logs' => $recent_logs,
            'region_totals' => $formatted_totals
        ]);
        exit;
    }

    if ($action === 'submit_prayer' && $_SERVER['REQUEST_METHOD'] === 'POST') {$input = json_decode(file_get_contents('php://input'), true);
        $fields =$input['fields'] ?? [];
        $region =$input['region'] ?? 'NA';

        if (empty($fields) || count($fields) > 4) {
            echo json_encode(['success' => false, 'error' => 'Please select between 1 and 4 prayer fields.']);
            exit;
        }

        $field_string = implode(', ',$fields);

        // 1. Insert prayer log
        Supabase::insert('prayer_logs', [
            'selected_fields' => $field_string,
            'region_code' => $region
        ]);

        // 2. Increment counts using Supabase RPC function
        foreach ($fields as$cat) {
            Supabase::rpc('increment_prayer_count', ['cat_name' => $cat]);
        }

        $random_verse = $kjv_verses[array_rand($kjv_verses)];

        echo json_encode(['success' => true, 'verse' => $random_verse, 'fields' => $fields, 'region' =>$region]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enreach - Pray4TheWorld</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --amber-50: #FFFBEB;
            --amber-100: #FEF3C7;
            --amber-500: #F59E0B;
            --amber-600: #D97706;
            --amber-700: #B45309;
            --amber-900: #78350F;
            --cream-bg: #FFFDF9;
            --cream-card: #FAF6F0;
            --border-color: #F3E8D5;
        }

        body {
            background-color: var(--cream-bg);
            color: #374151;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .map-continent {
            transition: fill 0.5s ease, filter 0.3s ease, transform 0.3s ease;
            cursor: pointer;
        }

        .map-continent:hover {
            filter: brightness(1.15) drop-shadow(0px 4px 8px rgba(217, 119, 6, 0.3));
        }

        .page-section {
            display: none;
            animation: fadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .page-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pulse-ring {
            animation: pulseGlow 2s infinite;
        }

        @keyframes pulseGlow {
            0% { r: 6px; opacity: 0.8; }
            50% { r: 14px; opacity: 0.2; }
            100% { r: 6px; opacity: 0.8; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

    <header class="bg-[#ffffff] border-b-2 border-[#F3E8D5] sticky top-0 z-50">
        <nav class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <img src="enreach.png" style="width:10%; height:auto;" alt="Enreach Logo">
                <span class="text-2xl font-bold tracking-wide text-[#D97706]">Enreach</span>
            </div>
            <ul class="flex gap-6 font-semibold text-gray-700">
                <li><button onclick="showSection('pray')" id="nav-pray" class="hover:text-[#D97706] transition-colors text-[#D97706] pb-1 border-b-2 border-[#D97706]">Pray4TheWorld</button></li>
                <li><button onclick="showSection('about')" id="nav-about" class="hover:text-[#D97706] transition-colors pb-1">About</button></li>
                <li><button onclick="showSection('support')" id="nav-support" class="hover:text-[#D97706] transition-colors pb-1">Support</button></li>
            </ul>
        </nav>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-8 flex-1 w-full">
        
        <!-- PRAY4THEWORLD SECTION -->
        <section id="pray" class="page-section active space-y-8">
            
            <div class="bg-[#FAF6F0] border border-[#F3E8D5] rounded-2xl p-6 md:p-8 shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-[#D97706]">Pray for the World</h2>
                        <p class="text-gray-600 mt-1">Select your location and 1 to 4 topics you wish to pray for globally:</p>
                    </div>
                    <span id="counterBadge" class="bg-[#FEF3C7] text-[#78350F] text-xs font-bold px-3 py-1.5 rounded-full border border-[#D97706]/20">
                        0 / 4 Selected
                    </span>
                </div>

                <div class="mb-5 bg-[#FFFDF9] p-4 rounded-xl border border-[#F3E8D5]">
                    <label class="block text-xs font-bold text-[#78350F] uppercase tracking-wider mb-2.5">
                        📍 Select Your Continent / Location for Live Mapping:
                    </label>
                    <div class="flex flex-wrap gap-2" id="regionSelector">
                        <button type="button" onclick="selectRegion('NA')" data-region="NA" class="region-btn px-3.5 py-2 rounded-xl border-2 border-[#D97706] bg-[#D97706] text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1.5">
                            <span>🌎</span> North America (NA)
                        </button>
                        <button type="button" onclick="selectRegion('SA')" data-region="SA" class="region-btn px-3.5 py-2 rounded-xl border-2 border-[#F3E8D5] bg-[#FFFDF9] text-gray-700 text-xs font-semibold hover:border-[#D97706] transition-all flex items-center gap-1.5">
                            <span>🌎</span> South America (SA)
                        </button>
                        <button type="button" onclick="selectRegion('EU')" data-region="EU" class="region-btn px-3.5 py-2 rounded-xl border-2 border-[#F3E8D5] bg-[#FFFDF9] text-gray-700 text-xs font-semibold hover:border-[#D97706] transition-all flex items-center gap-1.5">
                            <span>🌍</span> Europe (EU)
                        </button>
                        <button type="button" onclick="selectRegion('AF')" data-region="AF" class="region-btn px-3.5 py-2 rounded-xl border-2 border-[#F3E8D5] bg-[#FFFDF9] text-gray-700 text-xs font-semibold hover:border-[#D97706] transition-all flex items-center gap-1.5">
                            <span>🌍</span> Africa (AF)
                        </button>
                        <button type="button" onclick="selectRegion('AS')" data-region="AS" class="region-btn px-3.5 py-2 rounded-xl border-2 border-[#F3E8D5] bg-[#FFFDF9] text-gray-700 text-xs font-semibold hover:border-[#D97706] transition-all flex items-center gap-1.5">
                            <span>🌏</span> Asia (AS)
                        </button>
                        <button type="button" onclick="selectRegion('OC')" data-region="OC" class="region-btn px-3.5 py-2 rounded-xl border-2 border-[#F3E8D5] bg-[#FFFDF9] text-gray-700 text-xs font-semibold hover:border-[#D97706] transition-all flex items-center gap-1.5">
                            <span>🌏</span> Oceania (OC)
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2.5 my-6" id="categoryGrid"></div>

                <button id="submitPrayerBtn" onclick="submitPrayer()" disabled class="bg-[#D97706] hover:bg-[#B45309] disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold px-6 py-3 rounded-xl transition-all shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    Send World Prayer
                </button>

                <div id="verseBox" class="hidden mt-6 bg-[#FEF3C7] border-l-4 border-[#D97706] rounded-r-xl p-5 shadow-inner transition-all">
                    <p id="verseText" class="italic text-lg text-[#78350F] font-serif leading-relaxed"></p>
                    <p id="verseRef" class="text-right font-bold text-[#B45309] mt-2 text-sm tracking-wide"></p>
                </div>
            </div>

            <!-- Dashboard Analytics Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 bg-[#FAF6F0] border border-[#F3E8D5] rounded-2xl p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-[#D97706]">Prayed Fields Insights</h3>
                            <p class="text-xs text-gray-500">Global prayer density heat map by continent</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-[#78350F] bg-[#FEF3C7] px-3 py-1 rounded-full">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#D97706]"></span> Live Map Intensity
                        </div>
                    </div>

                    <div class="relative w-full overflow-hidden rounded-xl border border-[#F3E8D5] bg-[#FFFDF9] p-4">
                        <svg id="worldMapSvg" viewBox="0 0 1000 500" class="w-full h-auto drop-shadow-sm">
                            <defs>
                                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#F3E8D5" stroke-width="0.5"/>
                                </pattern>
                            </defs>
                            <rect width="1000" height="500" fill="url(#grid)" opacity="0.6"/>

                            <path id="continent-NA" data-region="NA" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" d="M 120 80 L 220 70 L 280 110 L 320 140 L 260 170 L 220 220 L 180 250 L 160 210 L 130 180 L 90 140 Z"/>
                            <path id="continent-SA" data-region="SA" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" d="M 230 270 L 310 270 L 340 330 L 300 420 L 260 450 L 240 380 L 220 310 Z"/>
                            <path id="continent-EU" data-region="EU" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" d="M 440 80 L 540 70 L 590 110 L 560 160 L 480 170 L 440 130 Z"/>
                            <path id="continent-AF" data-region="AF" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" d="M 450 190 L 570 180 L 610 240 L 580 340 L 520 380 L 470 310 L 440 240 Z"/>
                            <path id="continent-AS" data-region="AS" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" d="M 600 80 L 820 60 L 900 120 L 860 220 L 740 260 L 620 220 L 580 140 Z"/>
                            <path id="continent-OC" data-region="OC" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" d="M 760 310 L 880 300 L 910 380 L 830 420 L 750 380 Z"/>

                            <g id="mapNodes">
                                <circle cx="200" cy="150" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="200" cy="150" r="5" fill="#B45309"/>
                                <text x="200" y="130" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-NA">NA: 0</text>

                                <circle cx="270" cy="340" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="270" cy="340" r="5" fill="#B45309"/>
                                <text x="270" y="320" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-SA">SA: 0</text>

                                <circle cx="500" cy="120" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="500" cy="120" r="5" fill="#B45309"/>
                                <text x="500" y="100" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-EU">EU: 0</text>

                                <circle cx="520" cy="270" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="520" cy="270" r="5" fill="#B45309"/>
                                <text x="520" y="250" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-AF">AF: 0</text>

                                <circle cx="730" cy="150" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="730" cy="150" r="5" fill="#B45309"/>
                                <text x="730" y="130" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-AS">AS: 0</text>

                                <circle cx="830" cy="350" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="830" cy="350" r="5" fill="#B45309"/>
                                <text x="830" y="330" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-OC">OC: 0</text>
                            </g>
                        </svg>
                    </div>

                    <div class="mt-4 flex flex-wrap justify-between items-center text-xs text-gray-600 pt-2 border-t border-[#F3E8D5]">
                        <span class="font-semibold text-[#78350F]">Prayer Density Legend:</span>
                        <div class="flex items-center gap-4">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#FEF3C7] border border-[#D97706]"></span> Low</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#F59E0B]"></span> Medium</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#B45309]"></span> High</span>
                        </div>
                    </div>
                </div>

                <div class="bg-[#FAF6F0] border border-[#F3E8D5] rounded-2xl p-6 shadow-sm flex flex-col">
                    <h3 class="text-xl font-bold text-[#D97706] mb-1">Recent Prayer Logs</h3>
                    <p class="text-xs text-gray-500 mb-4">Real-time prayers sent by people worldwide</p>

                    <div class="flex-1 overflow-y-auto max-h-[320px] pr-2">
                        <ul class="space-y-3" id="historyList">
                            <li class="text-gray-500 text-sm italic">Loading recent prayers...</li>
                        </ul>
                    </div>
                </div>

            </div>
        </section>

        <!-- ABOUT SECTION -->
        <section id="about" class="page-section">
            <div class="bg-[#FAF6F0] border border-[#F3E8D5] rounded-2xl p-8 shadow-sm max-w-3xl mx-auto text-center my-8">
                <div class="w-16 h-16 bg-[#FEF3C7] rounded-full flex items-center justify-center text-[#D97706] text-2xl font-bold mx-auto mb-4">
                    ✝
                </div>
                <h2 class="text-3xl font-bold text-[#D97706] mb-2">Blessings to you!</h2>
                <p class="text-lg text-gray-700 leading-relaxed">
                    Enreach is an online application that aims to unite people, youth and elderlies, alike to have a common goal of praying for the world. Spread the good word and let the prayers count!
                </p>
            </div>
        </section>

        <!-- SUPPORT SECTION -->
        <section id="support" class="page-section">
            <div class="bg-[#FAF6F0] border border-[#F3E8D5] rounded-2xl p-16 shadow-sm text-center my-8">
                <h2 class="text-5xl md:text-6xl font-extrabold text-[#D97706] tracking-tight">Coming soon</h2>
                <p class="text-gray-500 mt-4 text-base">We are preparing options for supporting global missions and prayer causes.</p>
            </div>
        </section>

    </main>

    <footer class="bg-[#FAF6F0] border-t border-[#F3E8D5] py-4 text-center text-xs text-gray-500">
        Enreach Application &copy; <?php echo date('Y'); ?> — United in World Prayer
    </footer>

    <script>
        let selectedFields = [];
        let selectedRegion = 'NA';

        const regionNames = {
            'NA': 'North America',
            'SA': 'South America',
            'EU': 'Europe',
            'AF': 'Africa',
            'AS': 'Asia',
            'OC': 'Oceania'
        };

        let currentCategories = [];

        function getApiUrl(action) {
            return `?api=${action}`;
        }

        function showSection(sectionId) {
            document.querySelectorAll('.page-section').forEach(sec => sec.classList.remove('active'));
            document.querySelectorAll('header nav button').forEach(btn => {
                btn.classList.remove('text-[#D97706]', 'border-b-2', 'border-[#D97706]');
            });

            const activeSec = document.getElementById(sectionId);
            if (activeSec) activeSec.classList.add('active');

            const activeNav = document.getElementById(`nav-${sectionId}`);
            if (activeNav) {
                activeNav.classList.add('text-[#D97706]', 'border-b-2', 'border-[#D97706]');
            }
        }

        function selectRegion(code) {
            selectedRegion = code;
            document.querySelectorAll('#regionSelector .region-btn').forEach(btn => {
                if (btn.getAttribute('data-region') === code) {
                    btn.className = 'region-btn px-3.5 py-2 rounded-xl border-2 border-[#D97706] bg-[#D97706] text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1.5';
                } else {
                    btn.className = 'region-btn px-3.5 py-2 rounded-xl border-2 border-[#F3E8D5] bg-[#FFFDF9] text-gray-700 text-xs font-semibold hover:border-[#D97706] transition-all flex items-center gap-1.5';
                }
            });
        }

        function renderCategoryChips(categories) {
            if (categories && categories.length > 0) {
                currentCategories = categories;
            }
            const grid = document.getElementById('categoryGrid');
            if (!grid) return;
            grid.innerHTML = '';

            currentCategories.forEach(cat => {
                const isSelected = selectedFields.includes(cat.category_name);
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = isSelected
                    ? 'px-4 py-2.5 rounded-xl border-2 border-[#D97706] bg-[#D97706] text-white text-xs md:text-sm font-bold transition-all flex items-center gap-2 shadow-sm scale-102 cursor-pointer'
                    : 'px-4 py-2.5 rounded-xl border-2 border-[#F3E8D5] bg-[#FFFDF9] text-gray-700 text-xs md:text-sm font-semibold hover:border-[#D97706] transition-all flex items-center gap-2 shadow-2xs cursor-pointer';
                
                chip.innerHTML = `
                    <span>${cat.category_name}</span>
                    <span class="${isSelected ? 'bg-white/20 text-white' : 'bg-[#FEF3C7] text-[#78350F]'} text-xs font-bold px-2 py-0.5 rounded-full">${cat.prayer_count || 0}</span>
                `;
                
                chip.onclick = () => toggleSelectCategory(cat.category_name);
                grid.appendChild(chip);
            });
        }

        function toggleSelectCategory(categoryName) {
            const index = selectedFields.indexOf(categoryName);
            if (index > -1) {
                selectedFields.splice(index, 1);
            } else {
                if (selectedFields.length >= 4) return;
                selectedFields.push(categoryName);
            }

            renderCategoryChips(currentCategories);

            const counterBadge = document.getElementById('counterBadge');
            if (counterBadge) counterBadge.innerText = `${selectedFields.length} / 4 Selected`;

            const submitBtn = document.getElementById('submitPrayerBtn');
            if (submitBtn) submitBtn.disabled = selectedFields.length === 0;
        }

        async function loadDashboardData() {
            try {
                const response = await fetch(getApiUrl('get_data'));
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        renderCategoryChips(data.categories);
                        updateWorldMapChart(data.categories, data.region_totals);
                        renderHistory(data.recent_logs);
                    }
                }
            } catch (err) {
                console.error("Error loading dashboard data:", err);
            }
        }

        async function submitPrayer() {
            if (selectedFields.length === 0 || selectedFields.length > 4) return;

            try {
                const response = await fetch(getApiUrl('submit_prayer'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fields: selectedFields, region: selectedRegion })
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.verse) {
                        document.getElementById('verseText').innerText = `"${result.verse.text}"`;
                        document.getElementById('verseRef').innerText = `— ${result.verse.ref} (KJV)`;
                        document.getElementById('verseBox').classList.remove('hidden');
                    }
                }
            } catch (err) {
                console.error("Error submitting prayer:", err);
            }

            selectedFields = [];
            document.getElementById('counterBadge').innerText = `0 / 4 Selected`;
            document.getElementById('submitPrayerBtn').disabled = true;

            loadDashboardData();
        }

        function updateWorldMapChart(categories, regionTotalsApi) {
            const regionTotals = { NA: 0, SA: 0, EU: 0, AF: 0, AS: 0, OC: 0 };

            if (regionTotalsApi && Array.isArray(regionTotalsApi)) {
                regionTotalsApi.forEach(item => {
                    if (regionTotals[item.region_code] !== undefined) {
                        regionTotals[item.region_code] = parseInt(item.count);
                    }
                });
            }

            const maxCount = Math.max(...Object.values(regionTotals), 1);

            Object.keys(regionTotals).forEach(region => {
                const continentPath = document.getElementById(`continent-${region}`);
                const nodeText = document.getElementById(`node-text-${region}`);
                const count = regionTotals[region];

                if (nodeText) nodeText.textContent = `${region}: ${count}`;

                if (continentPath) {
                    const ratio = count / maxCount;
                    let fillColor = '#FEF3C7';

                    if (ratio > 0.6) fillColor = '#B45309';
                    else if (ratio > 0.3) fillColor = '#F59E0B';
                    else if (count > 0) fillColor = '#FBBF24';

                    continentPath.setAttribute('fill', fillColor);
                }
            });
        }

        function renderHistory(logs) {
            const list = document.getElementById('historyList');
            if (!list) return;
            list.innerHTML = '';

            if (!logs || logs.length === 0) {
                list.innerHTML = '<li class="text-gray-500 text-sm italic">No prayers offered yet. Be the first!</li>';
                return;
            }

            logs.forEach(log => {
                const regionName = regionNames[log.region_code] || log.region_code || 'Global';
                const li = document.createElement('li');
                li.className = 'text-sm text-gray-700 bg-[#FFFDF9] border border-[#F3E8D5] p-3 rounded-xl shadow-2xs';
                li.innerHTML = `🙏 A good samaritan in <span class="bg-[#FEF3C7] text-[#78350F] px-1.5 py-0.5 rounded font-bold text-xs">${regionName}</span> just prayed for <strong class="text-[#D97706]">${log.selected_fields}</strong>!`;
                list.appendChild(li);
            });
        }

        window.addEventListener('DOMContentLoaded', loadDashboardData);
    </script>
</body>
</html>