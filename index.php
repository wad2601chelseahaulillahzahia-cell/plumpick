<?php
$host = "localhost"; $user = "root"; $pass = "5november20061105"; $db = "king_store";
$conn = mysqli_connect($host, $user, $pass, $db);

// --- LOGIKA BACKEND ---
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action == 'clear_chat') mysqli_query($conn, "TRUNCATE TABLE offers");
    elseif ($action == 'register') {
        $name = mysqli_real_escape_string($conn, $_POST['fullname']);
        $addr = mysqli_real_escape_string($conn, $_POST['address']);
        mysqli_query($conn, "INSERT INTO users (fullname, address) VALUES ('$name', '$addr')");
    } elseif ($action == 'select_vendor') {
        $item = mysqli_real_escape_string($conn, $_POST['item_name']);
        $price = $_POST['price']; $seller = mysqli_real_escape_string($conn, $_POST['seller_name']); $qty = $_POST['qty'];
        mysqli_query($conn, "INSERT INTO offers (seller_name, item_name, price, category, total_price) VALUES ('$seller', '$item', $price, 'selection', $qty)");
    } elseif ($action == 'pay_now') mysqli_query($conn, "UPDATE offers SET category = 'paid' WHERE id = {$_POST['id']}");
    elseif ($action == 'cancel_order') mysqli_query($conn, "DELETE FROM offers WHERE id = {$_POST['id']}");
    elseif ($action == 'buyer_request') {
        $input = mysqli_real_escape_string($conn, $_POST['item_name']);
        mysqli_query($conn, "INSERT INTO offers (seller_name, item_name, price, category) VALUES ('PLUM-BOT', '$input', 0, 'request')");
    }
    echo json_encode(["status" => "success"]); exit;
}

// --- PENGAMBILAN DATA ---
$user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC LIMIT 1"));
$history_res = mysqli_query($conn, "SELECT * FROM offers ORDER BY created_at ASC");
$grand_total_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(price * total_price) as total FROM offers WHERE category='paid'"));
$payments_res = mysqli_query($conn, "SELECT * FROM offers WHERE category='paid' ORDER BY created_at DESC");

// --- LOGIKA SMART AUCTION ---
$last_req = mysqli_fetch_assoc(mysqli_query($conn, "SELECT item_name FROM offers WHERE category='request' ORDER BY created_at DESC LIMIT 1"));
$req_item = $last_req['item_name'] ?? '';
$auctions = []; $cheapest_id = null; $closest_id = null;
if($req_item) {
    $res = mysqli_query($conn, "SELECT * FROM sellers_products WHERE '$req_item' LIKE CONCAT('%', item_name, '%')");
    while($r = mysqli_fetch_assoc($res)) { $auctions[] = $r; }
    if (!empty($auctions)) {
        $prices = array_column($auctions, 'price');
        $cheapest_id = $auctions[array_search(min($prices), $prices)]['id'];
        $distances = array_column($auctions, 'distance_km');
        $closest_id = $auctions[array_search(min($distances), $distances)]['id'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PLUMPICK | It's Giving Savings</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6e169a; --dark: #3c0c42; --accent: #fbbf24; --bg: #f5f3f7; --success: #10b981; --danger: #ef4444; }
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); height: 100vh; display: flex; justify-content: center; align-items: center; padding: 10px; }
        .container { width: 100%; max-width: 1250px; height: 95vh; display: flex; gap: 20px; }
        .panel { background: white; border-radius: 25px; box-shadow: 0 10px 40px rgba(60,12,66,0.1); overflow: hidden; display: flex; flex-direction: column; }
        .chat-panel { flex: 1.2; }
        .side-panel { flex: 0.8; display: flex; flex-direction: column; gap: 15px; }
        .header { background: var(--primary); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        #chat-flow { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px; background: #fdfbff; }
        .bubble { padding: 12px 18px; border-radius: 18px; font-size: 0.9rem; max-width: 80%; }
        .req { background: var(--primary); color: white; align-self: flex-start; border-bottom-left-radius: 2px; }
        .card-msg { background: white; border: 2.5px solid var(--primary); border-radius: 20px; padding: 15px; align-self: flex-end; width: 280px; }
        .card-msg.paid { border-color: var(--success); background: #f0fff4; }
        .btn { border: none; padding: 10px; border-radius: 12px; cursor: pointer; font-weight: 800; width: 100%; margin-top: 5px; transition: 0.2s; }
        .btn-pay { background: var(--primary); color: white; }
        .chip-container { padding: 10px; display: flex; gap: 8px; overflow-x: auto; background: white; border-bottom: 1px solid #eee; }
        .chip { padding: 8px 15px; border-radius: 20px; border: 1.5px solid var(--primary); background: none; color: var(--primary); font-weight: 600; cursor: pointer; font-size: 0.75rem; white-space: nowrap; }
        .badge { font-size: 0.65rem; font-weight: 800; padding: 3px 10px; border-radius: 50px; display: inline-block; margin-bottom: 5px; }
        .badge-cheap { background: var(--success); color: white; }
        .badge-close { background: var(--accent); color: var(--dark); }
        .card-auc { background: rgba(255,255,255,0.05); padding: 12px; border-radius: 15px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.1); }
        .card-auc.highlight { border: 2px solid var(--accent); background: rgba(251, 191, 36, 0.1); }
        #payment-modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(60,12,66,0.8); backdrop-filter: blur(5px); justify-content: center; align-items: center; }
        .modal-content { background: white; width: 90%; max-width: 500px; border-radius: 25px; padding: 25px; max-height: 80vh; overflow-y: auto; }
        .history-item { border-bottom: 1px solid #eee; padding: 12px 0; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

<div id="payment-modal">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="color:var(--primary);">🧾 Payment Archive</h2>
            <button onclick="toggleModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div id="payment-list">
            <?php if(mysqli_num_rows($payments_res) > 0): ?>
                <?php while($p = mysqli_fetch_assoc($payments_res)): ?>
                    <div class="history-item">
                        <div>
                            <p style="font-weight:800;"><?= $p['item_name'] ?></p>
                            <small><?= $p['seller_name'] ?> • <?= date('d M, H:i', strtotime($p['created_at'])) ?></small>
                        </div>
                        <p style="font-weight:800; color:var(--success);">Rp <?= number_format($p['price'] * $p['total_price']) ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; color:#999; padding:20px;">No receipts found.</p>
            <?php endif; ?>
        </div>
        <button class="btn btn-pay" style="margin-top:15px;" onclick="toggleModal()">Dismiss</button>
    </div>
</div>

<div class="container">
    <div class="chat-panel panel">
        <div class="header">
            <div>
                <h3>PLUMPICK ✨</h3>
                <small><?= $user_data['fullname'] ?? 'User' ?> | <b>Total: Rp <?= number_format($grand_total_res['total'] ?? 0) ?></b></small>
            </div>
            <button class="btn" style="width:auto; padding:5px 12px; background:rgba(255,255,255,0.2); color:white; font-size:0.7rem;" onclick="ajaxAction('clear_chat')">Clear Chat</button>
        </div>
        <div class="chip-container">
            <?php $items = ['Es Krim','Nasi Goreng','Kopi','Ayam Geprek','Mie','Burger','Es Teh','Pizza','Seblak','Sate']; 
            foreach($items as $it) echo "<button class='chip' onclick='sendReq(\"$it\")'>$it</button>"; ?>
        </div>
        <div id="chat-flow">
            <?php while($r = mysqli_fetch_assoc($history_res)): ?>
                <?php if($r['category'] === 'request'): ?>
                    <div class="bubble req"><b>Plum Bot:</b> checking the vibes for <b><?= $r['item_name'] ?></b>... 🔍</div>
                <?php else: $isP = ($r['category'] === 'paid'); ?>
                    <div class="card-msg <?= $isP ? 'paid' : '' ?>">
                        <small style="font-weight:800; color:var(--primary);"><?= $isP ? '✅ SECURED' : 'CONFIRM ORDER' ?></small>
                        <h4><?= $r['seller_name'] ?></h4>
                        <p style="font-size:0.75rem;"><?= $r['item_name'] ?> x <?= $r['total_price'] ?></p>
                        <p style="font-weight:800; margin:5px 0;">Rp <?= number_format($r['price'] * $r['total_price']) ?></p>
                        <?php if(!$isP): ?>
                            <button class="btn btn-pay" onclick="ajaxAction('pay_now', {id:<?= $r['id'] ?>})">PAY NOW</button>
                            <button class="btn" style="background:none; color:var(--danger); font-size:0.7rem;" onclick="ajaxAction('cancel_order', {id:<?= $r['id'] ?>})">Cancel</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endwhile; ?>
        </div>
        <div style="padding:15px; display:flex; gap:10px;">
            <input type="text" id="uIn" placeholder="What are we buying today?..." style="flex:1; padding:12px; border-radius:15px; border:1px solid #ddd; outline:none;">
            <button onclick="sendReq(document.getElementById('uIn').value)" style="background:var(--primary); color:white; border:none; width:45px; border-radius:50%;">➔</button>
        </div>
    </div>

    <div class="side-panel">
        <button class="btn btn-pay" style="padding:15px; border-radius:20px; box-shadow: 0 5px 15px rgba(110,22,154,0.3);" onclick="toggleModal()">
            📋 View All Receipts
        </button>

        <div class="panel" style="padding:15px;">
            <h4 style="color:var(--primary);">👤 PROFILE</h4>
            <input type="text" id="regName" placeholder="Name" value="<?= $user_data['fullname'] ?? '' ?>" style="width:100%; padding:8px; margin:5px 0; border-radius:8px; border:1px solid #ddd;">
            <input type="text" id="regAddr" placeholder="Address" value="<?= $user_data['address'] ?? '' ?>" style="width:100%; padding:8px; margin-bottom:10px; border-radius:8px; border:1px solid #ddd;">
            <button class="btn btn-pay" onclick="ajaxAction('register', {fullname:document.getElementById('regName').value, address:document.getElementById('regAddr').value})">Update Profile</button>
        </div>

        <div class="panel" style="flex:1; background:var(--dark); color:white; padding:15px;">
            <h4 style="color:var(--accent);">🔨 TENDERS</h4>
            <div style="overflow-y:auto; flex:1;">
                <?php 
                foreach($auctions as $a): 
                    $isCheap = ($a['id'] == $cheapest_id); $isClose = ($a['id'] == $closest_id);
                ?>
                <div class="card-auc <?= ($isCheap || $isClose) ? 'highlight' : '' ?>">
                    <?php if($isCheap) echo '<span class="badge badge-cheap">💰 NO CAP CHEAPEST</span> '; ?>
                    <?php if($isClose) echo '<span class="badge badge-close">📍 REAL ONE (CLOSEST)</span>'; ?>
                    <h4 style="color:var(--accent);"><?= $a['shop_name'] ?></h4>
                    <p style="font-size:0.75rem; opacity:0.8;"><?= $a['distance_km'] ?> KM • <?= $a['item_name'] ?></p>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                        <span style="font-weight:800;">Rp <?= number_format($a['price']) ?></span>
                        <div style="display:flex; gap:5px;">
                            <input type="number" id="q-<?= $a['id'] ?>" value="1" min="1" style="width:40px; border-radius:5px; text-align:center;">
                            <button class="btn" style="background:var(--accent); color:var(--dark); margin:0; width:auto; padding:5px 10px;" onclick="ajaxAction('select_vendor', {seller_name:'<?= addslashes($a['shop_name']) ?>', item_name:'<?= addslashes($a['item_name']) ?>', price:<?= $a['price'] ?>, qty:document.getElementById('q-<?= $a['id'] ?>').value})">PICK</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const cf = document.getElementById('chat-flow'); cf.scrollTop = cf.scrollHeight;
    function ajaxAction(action, data = {}) {
        let fd = new FormData(); fd.append('action', action);
        for (let key in data) fd.append(key, data[key]);
        fetch('', { method: 'POST', body: fd }).then(() => location.reload());
    }
    function sendReq(it) { if(it) ajaxAction('buyer_request', {item_name: it}); }
    function toggleModal() {
        const modal = document.getElementById('payment-modal');
        modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
    }
</script>
</body>
</html>