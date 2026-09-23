<?php
// ==============================================================================
// 1. DATABASE AUTOMATIC SETUP & CONNECTION
// ==============================================================================
$db_host = 'localhost';
$db_name = 'enreach_db';
$db_user = 'root';
$db_pass = ''; // Adjust to your MySQL server password if needed

try {
    // Initial PDO connection to server root (creates DB automatically if missing)
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");

    // Table: Prayer activity logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS prayer_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        selected_fields VARCHAR(255) NOT NULL,
        region_code VARCHAR(10) NOT NULL DEFAULT 'NA',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Table: Prayer category counter with region allocation
    $pdo->exec("CREATE TABLE IF NOT EXISTS prayer_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(50) NOT NULL UNIQUE,
        region_code VARCHAR(10) NOT NULL DEFAULT 'NA',
        prayer_count INT DEFAULT 0
    )");

    // Initial default categories mapped to world regions for global prayer visualization
    $seed_categories = [
        ['Peace', 'NA'],     // North America
        ['Family', 'SA'],    // South America
        ['Friends', 'EU'],   // Europe
        ['Courage', 'AF'],   // Africa
        ['Honesty', 'AS'],   // Asia
        ['Blessings', 'OC'], // Oceania
        ['Hope', 'NA'],
        ['Wisdom', 'EU'],
        ['Healing', 'AF'],
        ['Love', 'AS'],
        ['Patience', 'SA'],
        ['Unity', 'OC']
    ];

    $stmtSeed = $pdo->prepare("INSERT IGNORE INTO prayer_categories (category_name, region_code, prayer_count) VALUES (?, ?, 0)");
    foreach ($seed_categories as $cat) {
        $stmtSeed->execute([$cat[0], $cat[1]]);
    }

} catch (PDOException $e) {
    // Graceful fallback for standalone viewing when MySQL connection is unavailable
    $pdo = null;
}

// ==============================================================================
// 2. 100 KING JAMES VERSION (KJV) ONLY BIBLE VERSES
// ==============================================================================
$kjv_verses = [
    ["ref" => "John 3:16", "text" => "For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life."],
    ["ref" => "Psalm 23:1", "text" => "The LORD is my shepherd; I shall not want."],
    ["ref" => "Philippians 4:13", "text" => "I can do all things through Christ which strengtheneth me."],
    ["ref" => "Proverbs 3:5", "text" => "Trust in the LORD with all thine heart; and lean not unto thine own understanding."],
    ["ref" => "Proverbs 3:6", "text" => "In all thy ways acknowledge him, and he shall direct thy paths."],
    ["ref" => "Romans 8:28", "text" => "And we know that all things work together for good to them that love God, to them who are the called according to his purpose."],
    ["ref" => "Isaiah 40:31", "text" => "But they that wait upon the LORD shall renew their strength; they shall mount up with wings as eagles; they shall run, and not be weary; and they shall walk, and not faint."],
    ["ref" => "Jeremiah 29:11", "text" => "For I know the thoughts that I think toward you, saith the LORD, thoughts of peace, and not of evil, to give you an expected end."],
    ["ref" => "Joshua 1:9", "text" => "Have not I commanded thee? Be strong and of a good courage; be not afraid, neither be thou dismayed: for the LORD thy God is with thee whithersoever thou goest."],
    ["ref" => "Matthew 6:33", "text" => "But seek ye first the kingdom of God, and his righteousness; and all these things shall be added unto you."],
    ["ref" => "Psalm 46:10", "text" => "Be still, and know that I am God: I will be exalted among the heathen, I will be exalted in the earth."],
    ["ref" => "Romans 12:2", "text" => "And be not conformed to this world: but be ye transformed by the renewing of your mind, that ye may prove what is that good, and acceptable, and perfect, will of God."],
    ["ref" => "Hebrews 11:1", "text" => "Now faith is the substance of things hoped for, the evidence of things not seen."],
    ["ref" => "1 Corinthians 13:4", "text" => "Charity suffereth long, and is kind; charity envieth not; charity vaunteth not itself, is not puffed up,"],
    ["ref" => "1 Corinthians 13:13", "text" => "And now abideth faith, hope, charity, these three; but the greatest of these is charity."],
    ["ref" => "Galatians 5:22-23", "text" => "But the fruit of the Spirit is love, joy, peace, longsuffering, gentleness, goodness, faith, Meekness, temperance: against such there is no law."],
    ["ref" => "Matthew 11:28", "text" => "Come unto me, all ye that labour and are heavy laden, and I will give you rest."],
    ["ref" => "Psalm 119:105", "text" => "Thy word is a lamp unto my feet, and a light unto my path."],
    ["ref" => "2 Timothy 1:7", "text" => "For God hath not given us the spirit of fear; but of power, and of love, and of a sound mind."],
    ["ref" => "Psalm 100:5", "text" => "For the LORD is good; his mercy is everlasting; and his truth endureth to all generations."],
    ["ref" => "1 John 4:19", "text" => "We love him, because he first loved us."],
    ["ref" => "Psalm 118:24", "text" => "This is the day which the LORD hath made; we will rejoice and be glad in it."],
    ["ref" => "Romans 15:13", "text" => "Now the God of hope fill you with all joy and peace in believing, that ye may abound in hope, through the power of the Holy Ghost."],
    ["ref" => "Isaiah 41:10", "text" => "Fear thou not; for I am with thee: be not dismayed; for I am thy God: I will strengthen thee; yea, I will help thee; yea, I will uphold thee with the right hand of my righteousness."],
    ["ref" => "Matthew 28:20", "text" => "Teaching them to observe all things whatsoever I have commanded you: and, lo, I am with you alway, even unto the end of the world. Amen."],
    ["ref" => "1 Thessalonians 5:16-18", "text" => "Rejoice evermore. Pray without ceasing. In every thing give thanks: for this is the will of God in Christ Jesus concerning you."],
    ["ref" => "Psalm 34:8", "text" => "O taste and see that the LORD is good: blessed is the man that trusteth in him."],
    ["ref" => "Psalm 91:1", "text" => "He that dwelleth in the secret place of the most High shall abide under the shadow of the Almighty."],
    ["ref" => "Romans 8:31", "text" => "What shall we then say to these things? If God be for us, who can be against us?"],
    ["ref" => "James 1:5", "text" => "If any of you lack wisdom, let him ask of God, that giveth to all men liberally, and upbraideth not; and it shall be given him."],
    ["ref" => "Proverbs 18:10", "text" => "The name of the LORD is a strong tower: the righteous runneth into it, and is safe."],
    ["ref" => "Matthew 5:16", "text" => "Let your light so shine before men, that they may see your good works, and glorify your Father which is in heaven."],
    ["ref" => "Psalm 19:14", "text" => "Let the words of my mouth, and the meditation of my heart, be acceptable in thy sight, O LORD, my strength, and my redeemer."],
    ["ref" => "John 14:6", "text" => "Jesus saith unto him, I am the way, the truth, and the life: no man cometh unto the Father, but by me."],
    ["ref" => "John 14:27", "text" => "Peace I leave with you, my peace I give unto you: not as the world giveth, give I unto you. Let not your heart be troubled, neither let it be afraid."],
    ["ref" => "Psalm 27:1", "text" => "The LORD is my light and my salvation; whom shall I fear? the LORD is the strength of my life; of whom shall I be afraid?"],
    ["ref" => "Ephesians 2:8", "text" => "For by grace are ye saved through faith; and that not of yourselves: it is the gift of God:"],
    ["ref" => "Ephesians 6:11", "text" => "Put on the whole armour of God, that ye may be able to stand against the wiles of the devil."],
    ["ref" => "1 Peter 5:7", "text" => "Casting all your care upon him; for he careth for you."],
    ["ref" => "2 Corinthians 5:17", "text" => "Therefore if any man be in Christ, he is a new creature: old things are passed away; behold, all things are become new."],
    ["ref" => "Psalm 37:4", "text" => "Delight thyself also in the LORD; and he shall give thee the desires of thine heart."],
    ["ref" => "Colossians 3:23", "text" => "And whatsoever ye do, do it heartily, as to the Lord, and not unto men;"],
    ["ref" => "Proverbs 16:3", "text" => "Commit thy works unto the LORD, and thy thoughts shall be established."],
    ["ref" => "Psalm 121:1-2", "text" => "I will lift up mine eyes unto the hills, from whence cometh my help. My help cometh from the LORD, which made heaven and earth."],
    ["ref" => "Matthew 7:7", "text" => "Ask, and it shall be given you; seek, and ye shall find; knock, and it shall be opened unto you:"],
    ["ref" => "Hebrews 13:8", "text" => "Jesus Christ the same yesterday, and to day, and for ever."],
    ["ref" => "1 John 1:9", "text" => "If we confess our sins, he is faithful and just to forgive us our sins, and to cleanse us from all unrighteousness."],
    ["ref" => "Romans 6:23", "text" => "For the wages of sin is death; but the gift of God is eternal life through Jesus Christ our Lord."],
    ["ref" => "John 8:32", "text" => "And ye shall know the truth, and the truth shall make you free."],
    ["ref" => "Revelation 3:20", "text" => "Behold, I stand at the door, and knock: if any man hear my voice, and open the door, I will come in to him, and will sup with him, and he with me."],
    ["ref" => "Psalm 1:1", "text" => "Blessed is the man that walketh not in the counsel of the ungodly, nor standeth in the way of sinners, nor sitteth in the seat of the scornful."],
    ["ref" => "Genesis 1:1", "text" => "In the beginning God created the heaven and the earth."],
    ["ref" => "Psalm 103:2", "text" => "Bless the LORD, O my soul, and forget not all his benefits:"],
    ["ref" => "Proverbs 4:23", "text" => "Keep thy heart with all diligence; for out of it are the issues of life."],
    ["ref" => "Isaiah 26:3", "text" => "Thou wilt keep him in perfect peace, whose mind is stayed on thee: because he trusteth in thee."],
    ["ref" => "Matthew 18:20", "text" => "For where two or three are gathered together in my name, there am I in the midst of them."],
    ["ref" => "Romans 10:9", "text" => "That if thou shalt confess with thy mouth the Lord Jesus, and shalt believe in thine heart that God hath raised him from the dead, thou shalt be saved."],
    ["ref" => "Galatians 2:20", "text" => "I am crucified with Christ: nevertheless I live; yet not I, but Christ liveth in me..."],
    ["ref" => "Ephesians 4:32", "text" => "And be ye kind one to another, tenderhearted, forgiving one another, even as God for Christ's sake hath forgiven you."],
    ["ref" => "Philippians 4:6", "text" => "Be careful for nothing; but in every thing by prayer and supplication with thanksgiving let your requests be made known unto God."],
    ["ref" => "Colossians 3:12", "text" => "Put on therefore, as the elect of God, holy and beloved, bowels of mercies, kindness, humbleness of mind, meekness, longsuffering;"],
    ["ref" => "1 Timothy 6:12", "text" => "Fight the good fight of faith, lay hold on eternal life, whereunto thou art also called..."],
    ["ref" => "2 Timothy 3:16", "text" => "All scripture is given by inspiration of God, and is profitable for doctrine, for reproof, for correction, for instruction in righteousness:"],
    ["ref" => "Hebrews 4:16", "text" => "Let us therefore come boldly unto the throne of grace, that we may obtain mercy, and find grace to help in time of need."],
    ["ref" => "James 4:8", "text" => "Draw nigh to God, and he will draw nigh to you."],
    ["ref" => "1 Peter 2:9", "text" => "But ye are a chosen generation, a royal priesthood, an holy nation, a peculiar people..."],
    ["ref" => "1 John 3:1", "text" => "Behold, what manner of love the Father hath bestowed upon us, that we should be called the sons of God..."],
    ["ref" => "Psalm 139:14", "text" => "I will praise thee; for I am fearfully and wonderfully made: marvellous are thy works; and that my soul knoweth right well."],
    ["ref" => "Proverbs 17:22", "text" => "A merry heart doeth good like a medicine: but a broken spirit drieth the bones."],
    ["ref" => "Isaiah 53:5", "text" => "But he was wounded for our transgressions, he was bruised for our iniquities... and with his stripes we are healed."],
    ["ref" => "Lamentations 3:22-23", "text" => "It is of the LORD's mercies that we are not consumed, because his compassions fail not. They are new every morning: great is thy faithfulness."],
    ["ref" => "Micah 6:8", "text" => "He hath shewed thee, O man, what is good; and what doth the LORD require of thee, but to do justly, and to love mercy, and to walk humbly with thy God?"],
    ["ref" => "Matthew 22:37", "text" => "Jesus said unto him, Thou shalt love the Lord thy God with all thy heart, and with all thy soul, and with all thy mind."],
    ["ref" => "Mark 10:27", "text" => "And Jesus looking upon them saith, With men it is impossible, but not with God: for with God all things are possible."],
    ["ref" => "Luke 1:37", "text" => "For with God nothing shall be impossible."],
    ["ref" => "John 10:10", "text" => "The thief cometh not, but for to steal, and to kill, and to destroy: I am come that they might have life, and that they might have it more abundantly."],
    ["ref" => "John 15:13", "text" => "Greater love hath no man than this, that a man lay down his life for his friends."],
    ["ref" => "Romans 5:8", "text" => "But God commendeth his love toward us, in that, while we were yet sinners, Christ died for us."],
    ["ref" => "1 Corinthians 10:13", "text" => "There hath no temptation taken you but such as is common to man: but God is faithful, who will not suffer you to be tempted above that ye are able..."],
    ["ref" => "2 Corinthians 12:9", "text" => "And he said unto me, My grace is sufficient for thee: for my strength is made perfect in weakness."],
    ["ref" => "Galatians 6:9", "text" => "And let us not be weary in well doing: for in due season we shall reap, if we faint not."],
    ["ref" => "Ephesians 3:20", "text" => "Now unto him that is able to do exceeding abundantly above all that we ask or think, according to the power that worketh in us,"],
    ["ref" => "Philippians 4:19", "text" => "But my God shall supply all your need according to his riches in glory by Christ Jesus."],
    ["ref" => "Colossians 3:14", "text" => "And above all these things put on charity, which is the bond of perfectness."],
    ["ref" => "1 Thessalonians 5:11", "text" => "Wherefore comfort yourselves together, and edify one another, even as also ye do."],
    ["ref" => "Hebrews 12:2", "text" => "Looking unto Jesus the author and finisher of our faith..."],
    ["ref" => "James 1:17", "text" => "Every good gift and every perfect gift is from above, and cometh down from the Father of lights..."],
    ["ref" => "1 Peter 4:8", "text" => "And above all things have fervent charity among yourselves: for charity shall cover the multitude of sins."],
    ["ref" => "1 John 4:8", "text" => "He that loveth not knoweth not God; for God is love."],
    ["ref" => "Revelation 21:4", "text" => "And God shall wipe away all tears from their eyes; and there shall be no more death, neither sorrow, nor crying, neither shall there be any more pain..."],
    ["ref" => "Psalm 16:11", "text" => "Thou wilt shew me the path of life: in thy presence is fulness of joy; at thy right hand there are pleasures for evermore."],
    ["ref" => "Psalm 30:5", "text" => "Weeping may endure for a night, but joy cometh in the morning."],
    ["ref" => "Psalm 62:8", "text" => "Trust in him at all times; ye people, pour out your heart before him: God is a refuge for us."],
    ["ref" => "Proverbs 28:13", "text" => "He that covereth his sins shall not prosper: but whoso confesseth and forsaketh them shall have mercy."],
    ["ref" => "Isaiah 9:6", "text" => "For unto us a child is born, unto us a son is given: and the government shall be upon his shoulder: and his name shall be called Wonderful, Counsellor, The mighty God, The everlasting Father, The Prince of Peace."],
    ["ref" => "Matthew 6:9-10", "text" => "After this manner therefore pray ye: Our Father which art in heaven, Hallowed be thy name. Thy kingdom come. Thy will be done in earth, as it is in heaven."],
    ["ref" => "Luke 6:31", "text" => "And as ye would that men should do to you, do ye also to them likewise."],
    ["ref" => "John 1:1", "text" => "In the beginning was the Word, and the Word was with God, and the Word was God."],
    ["ref" => "Romans 12:12", "text" => "Rejoicing in hope; patient in tribulation; continuing instant in prayer;"],
    ["ref" => "2 Corinthians 1:3-4", "text" => "Blessed be God... the Father of mercies, and the God of all comfort; Who comforteth us in all our tribulation..."]
];

// ==============================================================================
// 3. API ENDPOINTS HANDLER
// ==============================================================================
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $action = $_GET['api'];

    if ($action === 'get_data') {
        if ($pdo) {
            $stmt = $pdo->query("SELECT category_name, region_code, prayer_count FROM prayer_categories ORDER BY prayer_count DESC");
            $categories = $stmt->fetchAll();

            $stmt = $pdo->query("SELECT selected_fields, region_code, created_at FROM prayer_logs ORDER BY id DESC LIMIT 10");
            $recent_logs = $stmt->fetchAll();

            $stmt = $pdo->query("SELECT region_code, COUNT(*) as count FROM prayer_logs GROUP BY region_code");
            $region_totals = $stmt->fetchAll();
        } else {
            // Mock data fallback if database server is unavailable
            $categories = [
                ['category_name' => 'Peace', 'region_code' => 'NA', 'prayer_count' => 14],
                ['category_name' => 'Family', 'region_code' => 'SA', 'prayer_count' => 9],
                ['category_name' => 'Friends', 'region_code' => 'EU', 'prayer_count' => 18],
                ['category_name' => 'Courage', 'region_code' => 'AF', 'prayer_count' => 11],
                ['category_name' => 'Honesty', 'region_code' => 'AS', 'prayer_count' => 7],
                ['category_name' => 'Blessings', 'region_code' => 'OC', 'prayer_count' => 15],
                ['category_name' => 'Hope', 'region_code' => 'NA', 'prayer_count' => 12],
                ['category_name' => 'Wisdom', 'region_code' => 'EU', 'prayer_count' => 8],
                ['category_name' => 'Healing', 'region_code' => 'AF', 'prayer_count' => 16],
                ['category_name' => 'Love', 'region_code' => 'AS', 'prayer_count' => 20],
                ['category_name' => 'Patience', 'region_code' => 'SA', 'prayer_count' => 6],
                ['category_name' => 'Unity', 'region_code' => 'OC', 'prayer_count' => 10]
            ];
            $recent_logs = [
                ['selected_fields' => 'Peace, Hope', 'region_code' => 'NA', 'created_at' => date('Y-m-d H:i:s')],
                ['selected_fields' => 'Love, Healing, Unity', 'region_code' => 'AF', 'created_at' => date('Y-m-d H:i:s')]
            ];
            $region_totals = [
                ['region_code' => 'NA', 'count' => 26],
                ['region_code' => 'SA', 'count' => 15],
                ['region_code' => 'EU', 'count' => 26],
                ['region_code' => 'AF', 'count' => 27],
                ['region_code' => 'AS', 'count' => 27],
                ['region_code' => 'OC', 'count' => 25]
            ];
        }

        echo json_encode(['success' => true, 'categories' => $categories, 'recent_logs' => $recent_logs, 'region_totals' => $region_totals]);
        exit;
    }

    if ($action === 'submit_prayer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $fields = $input['fields'] ?? [];
        $region = $input['region'] ?? 'NA';

        if (empty($fields) || count($fields) > 4) {
            echo json_encode(['success' => false, 'error' => 'Please select between 1 and 4 prayer fields.']);
            exit;
        }

        if ($pdo) {
            $field_string = implode(', ', $fields);
            $stmt = $pdo->prepare("INSERT INTO prayer_logs (selected_fields, region_code) VALUES (?, ?)");
            $stmt->execute([$field_string, $region]);

            $updateStmt = $pdo->prepare("UPDATE prayer_categories SET prayer_count = prayer_count + 1 WHERE category_name = ?");
            foreach ($fields as $cat) {
                $updateStmt->execute([$cat]);
            }
        }

        // Pick a random King James Bible verse
        $random_verse = $kjv_verses[array_rand($kjv_verses)];

        echo json_encode(['success' => true, 'verse' => $random_verse, 'fields' => $fields, 'region' => $region]);
        exit;
    }

    if ($action === 'submit_prayer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $fields = $input['fields'] ?? [];

        if (empty($fields) || count($fields) > 4) {
            echo json_encode(['success' => false, 'error' => 'Please select between 1 and 4 prayer fields.']);
            exit;
        }

        if ($pdo) {
            $field_string = implode(', ', $fields);
            $stmt = $pdo->prepare("INSERT INTO prayer_logs (selected_fields) VALUES (?)");
            $stmt->execute([$field_string]);

            $updateStmt = $pdo->prepare("UPDATE prayer_categories SET prayer_count = prayer_count + 1 WHERE category_name = ?");
            foreach ($fields as $cat) {
                $updateStmt->execute([$cat]);
            }
        }

        // Pick a random King James Bible verse
        $random_verse = $kjv_verses[array_rand($kjv_verses)];

        echo json_encode(['success' => true, 'verse' => $random_verse, 'fields' => $fields]);
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
            
            <!-- Prayer Selection Card -->
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

                <!-- Continent / Location Selector -->
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

                <!-- Chips Grid -->
                <div class="flex flex-wrap gap-2.5 my-6" id="categoryGrid">
                    <!-- Populated via Javascript -->
                </div>

                <button id="submitPrayerBtn" onclick="submitPrayer()" disabled class="bg-[#D97706] hover:bg-[#B45309] disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold px-6 py-3 rounded-xl transition-all shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    Send World Prayer
                </button>

                <!-- KJV Verse Display Panel -->
                <div id="verseBox" class="hidden mt-6 bg-[#FEF3C7] border-l-4 border-[#D97706] rounded-r-xl p-5 shadow-inner transition-all">
                    <p id="verseText" class="italic text-lg text-[#78350F] font-serif leading-relaxed"></p>
                    <p id="verseRef" class="text-right font-bold text-[#B45309] mt-2 text-sm tracking-wide"></p>
                </div>
            </div>

            <!-- Dashboard Analytics Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Custom SVG World Map Chart -->
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

                    <!-- World Map Container -->
                    <div class="relative w-full overflow-hidden rounded-xl border border-[#F3E8D5] bg-[#FFFDF9] p-4">
                        <svg id="worldMapSvg" viewBox="0 0 1000 500" class="w-full h-auto drop-shadow-sm">
                            
                            <!-- Map Oceans Background Grid -->
                            <defs>
                                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#F3E8D5" stroke-width="0.5"/>
                                </pattern>
                            </defs>
                            <rect width="1000" height="500" fill="url(#grid)" opacity="0.6"/>

                            <!-- CONTINENT PATHS -->
                            <!-- North America (NA) -->
                            <path id="continent-NA" data-region="NA" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" 
                                d="M 120 80 L 220 70 L 280 110 L 320 140 L 260 170 L 220 220 L 180 250 L 160 210 L 130 180 L 90 140 Z"/>
                            
                            <!-- South America (SA) -->
                            <path id="continent-SA" data-region="SA" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" 
                                d="M 230 270 L 310 270 L 340 330 L 300 420 L 260 450 L 240 380 L 220 310 Z"/>

                            <!-- Europe (EU) -->
                            <path id="continent-EU" data-region="EU" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" 
                                d="M 440 80 L 540 70 L 590 110 L 560 160 L 480 170 L 440 130 Z"/>

                            <!-- Africa (AF) -->
                            <path id="continent-AF" data-region="AF" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" 
                                d="M 450 190 L 570 180 L 610 240 L 580 340 L 520 380 L 470 310 L 440 240 Z"/>

                            <!-- Asia (AS) -->
                            <path id="continent-AS" data-region="AS" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" 
                                d="M 600 80 L 820 60 L 900 120 L 860 220 L 740 260 L 620 220 L 580 140 Z"/>

                            <!-- Australia / Oceania (OC) -->
                            <path id="continent-OC" data-region="OC" class="map-continent" fill="#FEF3C7" stroke="#D97706" stroke-width="1.5" 
                                d="M 760 310 L 880 300 L 910 380 L 830 420 L 750 380 Z"/>

                            <!-- Interactive Pulsing Location Nodes -->
                            <g id="mapNodes">
                                <!-- Node NA -->
                                <circle cx="200" cy="150" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="200" cy="150" r="5" fill="#B45309"/>
                                <text x="200" y="130" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-NA">NA: 0</text>

                                <!-- Node SA -->
                                <circle cx="270" cy="340" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="270" cy="340" r="5" fill="#B45309"/>
                                <text x="270" y="320" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-SA">SA: 0</text>

                                <!-- Node EU -->
                                <circle cx="500" cy="120" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="500" cy="120" r="5" fill="#B45309"/>
                                <text x="500" y="100" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-EU">EU: 0</text>

                                <!-- Node AF -->
                                <circle cx="520" cy="270" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="520" cy="270" r="5" fill="#B45309"/>
                                <text x="520" y="250" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-AF">AF: 0</text>

                                <!-- Node AS -->
                                <circle cx="730" cy="150" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="730" cy="150" r="5" fill="#B45309"/>
                                <text x="730" y="130" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-AS">AS: 0</text>

                                <!-- Node OC -->
                                <circle cx="830" cy="350" r="12" fill="#D97706" opacity="0.15" class="pulse-ring"/>
                                <circle cx="830" cy="350" r="5" fill="#B45309"/>
                                <text x="830" y="330" text-anchor="middle" font-size="11" font-weight="bold" fill="#78350F" id="node-text-OC">OC: 0</text>
                            </g>
                        </svg>
                    </div>

                    <!-- Map Legend -->
                    <div class="mt-4 flex flex-wrap justify-between items-center text-xs text-gray-600 pt-2 border-t border-[#F3E8D5]">
                        <span class="font-semibold text-[#78350F]">Prayer Density Legend:</span>
                        <div class="flex items-center gap-4">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#FEF3C7] border border-[#D97706]"></span> Low</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#F59E0B]"></span> Medium</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#B45309]"></span> High</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Prayer History Feed -->
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

    <!-- ONE-TIME TUTORIAL MODAL -->
    <div id="tutorialModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-[#FFFDF9] border-2 border-[#D97706] rounded-2xl p-6 md:p-8 max-w-md w-full shadow-2xl text-center space-y-5">
            <div class="w-12 h-12 bg-[#FEF3C7] rounded-full flex items-center justify-center mx-auto text-[#D97706]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707"></path></svg>
            </div>
            <p class="text-gray-800 text-lg leading-snug font-medium">
                Welcome to Enreach! In Pray4TheWorld, pray for four things for the world to have one day. Nothing's impossible with the Lord! Amen?
            </p>
            <button onclick="closeTutorial()" class="w-full bg-[#D97706] hover:bg-[#B45309] text-white font-bold py-3 rounded-xl transition-colors shadow-md text-lg">
                Amen!
            </button>
        </div>
    </div>

    <footer class="bg-[#FAF6F0] border-t border-[#F3E8D5] py-4 text-center text-xs text-gray-500">
        Enreach Application &copy; <?php echo date('Y'); ?> — United in World Prayer
    </footer>

    <!-- CLIENT SCRIPT -->
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

        // Fallback mock dataset for browser preview environments without an active PHP server
        let mockCategories = [
            { category_name: 'Peace', region_code: 'NA', prayer_count: 14 },
            { category_name: 'Family', region_code: 'SA', prayer_count: 9 },
            { category_name: 'Friends', region_code: 'EU', prayer_count: 18 },
            { category_name: 'Courage', region_code: 'AF', prayer_count: 11 },
            { category_name: 'Honesty', region_code: 'AS', prayer_count: 7 },
            { category_name: 'Blessings', region_code: 'OC', prayer_count: 15 },
            { category_name: 'Hope', region_code: 'NA', prayer_count: 12 },
            { category_name: 'Wisdom', region_code: 'EU', prayer_count: 8 },
            { category_name: 'Healing', region_code: 'AF', prayer_count: 16 },
            { category_name: 'Love', region_code: 'AS', prayer_count: 20 },
            { category_name: 'Patience', region_code: 'SA', prayer_count: 6 },
            { category_name: 'Unity', region_code: 'OC', prayer_count: 10 }
        ];

        let currentCategories = [...mockCategories];

        let mockLogs = [
            { selected_fields: 'Peace, Hope', region_code: 'NA', created_at: new Date().toISOString() },
            { selected_fields: 'Love, Healing, Unity', region_code: 'AF', created_at: new Date().toISOString() }
        ];

        let mockRegionTotals = { NA: 26, SA: 15, EU: 26, AF: 27, AS: 27, OC: 25 };

        const clientKjvVerses = [
            { ref: "John 3:16", text: "For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life." },
            { ref: "Psalm 23:1", text: "The LORD is my shepherd; I shall not want." },
            { ref: "Philippians 4:13", text: "I can do all things through Christ which strengtheneth me." },
            { ref: "Proverbs 3:5", text: "Trust in the LORD with all thine heart; and lean not unto thine own understanding." },
            { ref: "Isaiah 40:31", text: "But they that wait upon the LORD shall renew their strength; they shall mount up with wings as eagles..." }
        ];

        function getApiUrl(action) {
            const loc = window.location;
            if (loc.protocol === 'blob:' || loc.protocol === 'about:' || !loc.pathname || loc.pathname === 'blank') {
                return `?api=${action}`;
            }
            return `${loc.protocol}//${loc.host}${loc.pathname}?api=${action}`;
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

        function checkTutorial() {
            if (!localStorage.getItem('enreach_tutorial_seen')) {
                const modal = document.getElementById('tutorialModal');
                if (modal) modal.classList.remove('hidden');
            }
        }

        function closeTutorial() {
            localStorage.setItem('enreach_tutorial_seen', 'true');
            const modal = document.getElementById('tutorialModal');
            if (modal) modal.classList.add('hidden');
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
                if (selectedFields.length >= 4) {
                    return;
                }
                selectedFields.push(categoryName);
            }

            renderCategoryChips(currentCategories);

            const counterBadge = document.getElementById('counterBadge');
            if (counterBadge) {
                counterBadge.innerText = `${selectedFields.length} / 4 Selected`;
            }

            const submitBtn = document.getElementById('submitPrayerBtn');
            if (submitBtn) {
                submitBtn.disabled = selectedFields.length === 0;
            }
        }

        async function loadDashboardData() {
            try {
                const url = getApiUrl('get_data');
                const response = await fetch(url);
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        mockCategories = data.categories;
                        mockLogs = data.recent_logs;
                        renderCategoryChips(data.categories);
                        updateWorldMapChart(data.categories, data.region_totals);
                        renderHistory(data.recent_logs);
                        return;
                    }
                }
            } catch (err) {
                console.warn("API unavailable, rendering with client-side fallback data:", err);
            }

            // Fallback rendering when running in blob iframe or without PHP server
            renderCategoryChips(mockCategories);
            updateWorldMapChart(mockCategories, null);
            renderHistory(mockLogs);
        }

        async function submitPrayer() {
            if (selectedFields.length === 0 || selectedFields.length > 4) return;

            let verseToDisplay = null;

            try {
                const url = getApiUrl('submit_prayer');
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fields: selectedFields, region: selectedRegion })
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        verseToDisplay = result.verse;
                    }
                }
            } catch (err) {
                console.warn("API submission unavailable, processing locally:", err);
            }

            // Local fallback logic if backend server is not reachable
            if (!verseToDisplay) {
                selectedFields.forEach(fName => {
                    const found = currentCategories.find(c => c.category_name === fName);
                    if (found) found.prayer_count = parseInt(found.prayer_count) + 1;
                });
                
                if (!mockRegionTotals[selectedRegion]) mockRegionTotals[selectedRegion] = 0;
                mockRegionTotals[selectedRegion] += 1;

                mockLogs.unshift({
                    selected_fields: selectedFields.join(', '),
                    region_code: selectedRegion,
                    created_at: new Date().toISOString()
                });
                verseToDisplay = clientKjvVerses[Math.floor(Math.random() * clientKjvVerses.length)];
            }

            // Display KJV Bible Verse
            document.getElementById('verseText').innerText = `"${verseToDisplay.text}"`;
            document.getElementById('verseRef').innerText = `— ${verseToDisplay.ref} (KJV)`;
            document.getElementById('verseBox').classList.remove('hidden');

            // Reset selection
            selectedFields = [];
            document.getElementById('counterBadge').innerText = `0 / 4 Selected`;
            document.getElementById('submitPrayerBtn').disabled = true;

            // Reload map & categories
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
            } else if (mockRegionTotals) {
                Object.assign(regionTotals, mockRegionTotals);
            } else {
                categories.forEach(cat => {
                    if (regionTotals[cat.region_code] !== undefined) {
                        regionTotals[cat.region_code] += parseInt(cat.prayer_count);
                    }
                });
            }

            const maxCount = Math.max(...Object.values(regionTotals), 1);

            Object.keys(regionTotals).forEach(region => {
                const continentPath = document.getElementById(`continent-${region}`);
                const nodeText = document.getElementById(`node-text-${region}`);
                const count = regionTotals[region];

                if (nodeText) {
                    nodeText.textContent = `${region}: ${count}`;
                }

                if (continentPath) {
                    const ratio = count / maxCount;
                    let fillColor = '#FEF3C7'; // Light amber default

                    if (ratio > 0.6) {
                        fillColor = '#B45309'; // Deep Amber
                    } else if (ratio > 0.3) {
                        fillColor = '#F59E0B'; // Medium Amber
                    } else if (count > 0) {
                        fillColor = '#FBBF24'; // Warm Amber
                    }

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

        window.addEventListener('DOMContentLoaded', () => {
            checkTutorial();
            loadDashboardData();
        });
    </script>
</body>
</html>
```

### Key Highlights of this Update
1. **Prayer Topic Chip Selection Restored**: `renderCategoryChips` and `toggleSelectCategory` have been fully linked. You can click any topic chip to toggle it on or off (up to 4 max).
2. **Dynamic Badges & Counts**: Each prayer chip displays its live global count badge (e.g. `Peace (14)`), which updates dynamically after each prayer submission.
3. **Location + Topic Coexistence**: Users choose both their continent location (for the live heatmap) and 1 to 4 prayer topics before clicking **Send World Prayer**.
4. **Counter & Button State Syncing**: The `0 / 4 Selected` badge and the **Send World Prayer** submit button enable/disable accurately based on active topic selections.