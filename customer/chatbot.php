<?php
/* ===========================================================
   /customer/chatbot.php   —   Verve Timepieces (All-in-One)
   ===========================================================

   This file contains:
   1) PHP: JSON API handler (POST) — runs BEFORE any HTML
   2) HTML: floating chatbot widget
   3) CSS : luxury watch theme
   4) JS  : click handler + fetch to THIS file

   REQUIREMENTS:
   - PDO $pdo defined in /includes/dbconnect.php (adjust path below)
   - Database schema from your dump (brands.brand_name, categories.cat_name, orders.order_number, etc.)
     (Matches your SQL export.)  [DB schema ref]  */
/* ===========================================================
   1) PHP — JSON API HANDLER
   =========================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    // SAFETY: ensure no stray output corrupts JSON
    if (ob_get_level()) {
        ob_end_clean();
    }

    declare(strict_types=1);
    session_start();

    // If your dbconnect.php is elsewhere, adjust this path:
    require_once 'dbconnect.php';

    // Never echo HTML — we’re returning JSON only
    header('Content-Type: application/json; charset=UTF-8');

    // Hide notices from the response (log them instead)
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    $msgRaw = trim((string)($_POST['message'] ?? ''));
    $msg    = mb_strtolower($msgRaw);

    $response = "I can help you browse watches, find deals, or track an order.";
    $options  = ["Browse Watches", "Best Deals", "Find by Brand", "Track Order", "Customer Support"];

    // --- small helpers ---
    $now = function (): string {
        return date('Y-m-d H:i:s'); 
    };

    $fetchAll = function (string $sql, array $params = []) use ($pdo): array {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    };

    $fetchOne = function (string $sql, array $params = []) use ($fetchAll): ?array {
        $rows = $fetchAll($sql, $params);
        return $rows ? $rows[0] : null;
    };

    // discounts for product (product, brand, category)
    $getDiscounts = function (int $pid) use ($fetchAll, $now): array {
        $byProduct = $fetchAll("
      SELECT d.* FROM product_discounts pd
      JOIN discounts d ON d.discount_id = pd.discount_id
      WHERE pd.product_id = :pid
        AND d.is_active = 1
        AND (d.starts_at IS NULL OR d.starts_at <= :now)
        AND (d.ends_at   IS NULL OR d.ends_at   >= :now)
    ", [':pid' => $pid, ':now' => $now()]);

        $byBC = $fetchAll("
      SELECT d.* FROM discounts d
      JOIN products p ON p.product_id = :pid
      WHERE d.is_active = 1
        AND (d.starts_at IS NULL OR d.starts_at <= :now)
        AND (d.ends_at   IS NULL OR d.ends_at   >= :now)
        AND (
          (d.brand_id     IS NOT NULL AND d.brand_id     = p.brand_id) OR
          (d.category_id  IS NOT NULL AND d.category_id  = p.category_id)
        )
    ", [':pid' => $pid, ':now' => $now()]);

        return array_merge($byProduct, $byBC);
    };

    $applyDiscounts = function (float $base, array $discounts): array {
        if (!$discounts) return [round($base, 2), []];
        $stacking = false;
        foreach ($discounts as $d) {
            if ((int)$d['allow_stacking'] === 1) {
                $stacking = true;
                break;
            }
        }
        $best = $base;
        $applied = [];
        if ($stacking) {
            $price = $base;
            foreach ($discounts as $d) {
                if ((int)$d['allow_stacking'] !== 1) continue;
                $price = ($d['kind'] === 'percent') ? $price * (1 - (float)$d['value'] / 100.0) : max(0, $price - (float)$d['value']);
                $applied[] = $d;
            }
            $best = $price;
        } else {
            foreach ($discounts as $d) {
                $price = ($d['kind'] === 'percent') ? $base * (1 - (float)$d['value'] / 100.0) : max(0, $base - (float)$d['value']);
                if ($price < $best) {
                    $best = $price;
                    $applied = [$d];
                }
            }
        }
        return [round($best, 2), $applied];
    };

    $renderLine = function (array $p, float $eff, array $applied): string {
        $disc = '';
        if ($applied) {
            $tags = array_map(function ($d) {
                return $d['kind'] === 'percent' ? ((float)$d['value']) . '%' : ('$' . number_format((float)$d['value'], 2));
            }, $applied);
            $disc = ' (Deal: ' . implode(' + ', $tags) . ')';
        }
        return "- {$p['brand_name']} {$p['product_name']} — $" . number_format($eff, 2) . $disc;
    };

    $listWatches = function (string $whereSql = '', array $params = [], int $limit = 6)
    use ($fetchAll, $getDiscounts, $applyDiscounts, $renderLine): array {
        $sql = "
      SELECT p.product_id, p.product_name, p.price, p.stock_quantity,
             b.brand_name, c.cat_name, dc.dial_color, cm.material, p.case_size
      FROM products p
      LEFT JOIN brands b        ON b.brand_id        = p.brand_id
      LEFT JOIN categories c    ON c.category_id     = p.category_id
      LEFT JOIN dial_colors dc  ON dc.dial_color_id  = p.dial_color_id
      LEFT JOIN case_materials cm ON cm.case_material_id = p.case_material_id
      $whereSql
      ORDER BY p.product_id DESC
      LIMIT $limit
    ";
        $rows = $fetchAll($sql, $params);
        $lines = [];
        foreach ($rows as $p) {
            [$eff, $applied] = $applyDiscounts((float)$p['price'], $getDiscounts((int)$p['product_id']));
            $lines[] = $renderLine($p, $eff, $applied);
        }
        return $lines;
    };

    try {
        // === INTENTS ===

        // greeting
        if (strpos($msg, 'hello') !== false || strpos($msg, 'hi') !== false) {
            $response = "Hi! I can help you browse watches, find deals, or track an order.";
            $options  = ["Browse Watches", "Best Deals", "Find by Brand", "Track Order", "Customer Support"];

            // browse
        } elseif ($msg === 'browse watches' || $msg === 'browse' || $msg === 'watches') {
            $lines = $listWatches("WHERE p.stock_quantity > 0", [], 6);
            $response = $lines ? "Here are a few in stock:<br>" . implode('<br>', $lines) : "Sorry, nothing in stock right now.";
            $options  = ["Best Deals", "Find by Brand", "Customer Support"];

            // deals
        } elseif ($msg === 'best deals' || $msg === 'deals' || $msg === 'discounts') {
            // score by % off
            $rows = $fetchAll("SELECT p.product_id, p.product_name, p.price, b.brand_name
                         FROM products p LEFT JOIN brands b ON b.brand_id = p.brand_id
                         WHERE p.stock_quantity > 0");
            $scored = [];
            foreach ($rows as $p) {
                $d = $getDiscounts((int)$p['product_id']);
                if (!$d) continue;
                [$eff, $applied] = $applyDiscounts((float)$p['price'], $d);
                $pct = ((float)$p['price'] > 0) ? round(100 * (1 - $eff / (float)$p['price'])) : 0;
                $scored[] = ['line' => $renderLine($p, $eff, $applied), 'pct' => $pct];
            }
            usort($scored, fn($a, $b) => $b['pct'] <=> $a['pct']);
            $top = array_map(fn($x) => $x['line'], array_slice($scored, 0, 5));
            $response = $top ? "Top deals right now:<br>" . implode('<br>', $top) : "No active deals at the moment.";
            $options  = ["Browse Watches", "Find by Brand", "Customer Support"];

            // support
        } elseif ($msg === 'customer support' || $msg === 'support' || $msg === 'help') {
            $response = "You can contact support at <b>support@verve-timepieces.example</b> or call <b>+95-XXX-XXX</b>.";
            $options  = ["Browse Watches", "Best Deals", "Find by Brand"];

            // track order EC1
        } elseif (preg_match('/^track\\s+order\\s+([A-Za-z0-9-]+)/', $msg, $m)) {
            $o = $fetchOne(
                "SELECT order_number,status,total,created_at FROM orders WHERE order_number = :n",
                [':n' => $m[1]]
            );
            if ($o) {
                $response = "Order <b>{$o['order_number']}</b> status: <b>{$o['status']}</b><br>Total: $"
                    . number_format((float)$o['total'], 2) . "<br>Placed: {$o['created_at']}";
                $options  = ["Browse Watches", "Best Deals"];
            } else {
                $response = "Sorry, I can't find that order number. Please check and try again.";
                $options  = ["Browse Watches", "Best Deals", "Customer Support"];
            }

            // free text search: brand/product/category/dial + price filters
        } else {
            $priceMin = null;
            $priceMax = null;
            if (preg_match('/under\\s*(\\d+)/', $msg, $m)) {
                $priceMax = (float)$m[1];
            }
            if (preg_match('/below\\s*(\\d+)/', $msg, $m)) {
                $priceMax = (float)$m[1];
            }
            if (preg_match('/over\\s*(\\d+)/',  $msg, $m)) {
                $priceMin = (float)$m[1];
            }
            if (preg_match('/above\\s*(\\d+)/', $msg, $m)) {
                $priceMin = (float)$m[1];
            }

            $terms = preg_split('/\\s+/', $msg);
            $likes = [];
            $params = [];
            $skip  = ['track', 'order', 'best', 'deals', 'browse', 'watches', 'find', 'by', 'brand', 'customer', 'support'];
            $i = 0;
            foreach ($terms as $t) {
                if (in_array($t, $skip, true)) continue;
                $likes[] = "(LOWER(p.product_name) LIKE :w$i OR LOWER(b.brand_name) LIKE :w$i OR LOWER(c.cat_name) LIKE :w$i OR LOWER(dc.dial_color) LIKE :w$i)";
                $params[":w$i"] = "%$t%";
                $i++;
            }
            $where = "WHERE p.stock_quantity > 0";
            if ($likes) {
                $where .= " AND (" . implode(' OR ', $likes) . ")";
            }
            if ($priceMin !== null) {
                $where .= " AND p.price >= :pmin";
                $params[':pmin'] = $priceMin;
            }
            if ($priceMax !== null) {
                $where .= " AND p.price <= :pmax";
                $params[':pmax'] = $priceMax;
            }

            $lines = $listWatches($where, $params, 6);
            $response = $lines ? "Here’s what I found:<br>" . implode('<br>', $lines)
                : "No matching watches found. Try a brand (e.g., 'Omega'), a dial color ('blue'), a category ('Dive'), or a price filter ('under 5000').";
            $options  = ["Best Deals", "Browse Watches", "Find by Brand", "Track Order"];
        }

        echo json_encode(['message' => $response, 'options' => $options], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        error_log('[chatbot.php] ' . $e->getMessage());
        // Keep 200 so JS .json() still runs and we can show a friendly error
        http_response_code(200);
        echo json_encode([
            'message' => "Sorry—something went wrong on our side. Please try again in a moment.",
            'options' => ["Browse Watches", "Best Deals", "Find by Brand"]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
// ---------- END JSON HANDLER ----------
?>

<!-- =========================================================
     2) HTML — widget
     ========================================================= -->
<div id="chatbot-container">
    <div id="chat-icon" title="Chat" aria-label="Open timepieces assistant">⌚</div>

    <div id="chat-window">
        <div id="chat-header">
            <span>Timepieces Assistant</span>
            <button type="button" onclick="toggleChat()" aria-label="Close">✖</button>
        </div>

        <div id="chat-messages">
            <p class="bot-msg">Hello! ⌚ Need help finding a watch or your order?</p>
        </div>

        <div id="chat-options"></div>

        <div id="chat-input-area">
            <input type="text" id="chat-input"
                placeholder="Try: Omega blue dial under 5000, best deals, track order EC1"
                onkeydown="if(event.key==='Enter') sendMessage()">
            <button type="button" onclick="sendMessage()">Send</button>
        </div>
    </div>
</div>

<!-- Ionicons (optional) -->
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

<!-- =========================================================
     3) CSS — luxury watch theme (overlay-proof click)
     ========================================================= -->
<style>
    :root {
        --vt-bg: #fff;
        --vt-panel: #0b1d2a;
        --vt-panel-2: #102a3a;
        --vt-accent: #c9a227;
        --vt-bot: #e9f2f1;
        --vt-user: #e8efe0;
        --vt-text: #1a1a1a;
        --vt-muted: #6b7280;
        --vt-border: #e5e7eb;
        --shadow: 0 12px 28px rgba(0, 0, 0, .25);
    }

    #chatbot-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 100000;
        font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
    }

    #chat-icon {
        background: var(--vt-panel);
        color: #fff;
        font-size: 28px;
        padding: 16px;
        border-radius: 16px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .3);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform .25s, box-shadow .25s;
        border: 1px solid rgba(255, 255, 255, .12);
        pointer-events: auto
    }

    #chat-icon:hover {
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 10px 24px rgba(0, 0, 0, .35)
    }

    #chat-window {
        display: none;
        flex-direction: column;
        width: 380px;
        max-width: calc(100vw - 32px);
        height: 520px;
        background: var(--vt-bg);
        border-radius: 14px;
        box-shadow: var(--shadow);
        overflow: hidden;
        margin-top: 12px;
        border: 1px solid var(--vt-border)
    }

    #chat-header {
        background: linear-gradient(180deg, var(--vt-panel), var(--vt-panel-2));
        color: #fff;
        padding: 14px 16px;
        font-weight: 600;
        font-size: 16px;
        letter-spacing: .2px;
        display: flex;
        justify-content: space-between;
        align-items: center
    }

    #chat-header button {
        background: none;
        border: none;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        padding: 6px;
        border-radius: 8px
    }

    #chat-header button:hover {
        background: rgba(255, 255, 255, .08)
    }

    #chat-messages {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        font-size: 14px;
        background: #fafafa
    }

    #chat-messages p {
        margin: 10px 0;
        line-height: 1.5
    }

    .user-msg {
        text-align: right;
        color: var(--vt-text);
        background: var(--vt-user);
        display: inline-block;
        padding: 8px 12px;
        border-radius: 14px;
        max-width: 80%;
        border: 1px solid #d9e0d2
    }

    .bot-msg {
        text-align: left;
        color: var(--vt-panel);
        background: var(--vt-bot);
        display: inline-block;
        padding: 8px 12px;
        border-radius: 14px;
        max-width: 80%;
        border: 1px solid #dfeeea
    }

    #chat-options {
        padding: 10px 12px;
        border-top: 1px solid var(--vt-border);
        background: #fff;
        display: flex;
        flex-wrap: wrap;
        gap: 8px
    }

    .chat-option-btn {
        background: var(--vt-panel);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, .15);
        border-radius: 999px;
        padding: 8px 14px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        transition: background .25s, transform .05s;
        white-space: nowrap
    }

    .chat-option-btn:hover {
        background: var(--vt-panel-2)
    }

    .chat-option-btn:active {
        transform: scale(.98)
    }

    #chat-input-area {
        display: flex;
        gap: 10px;
        padding: 12px;
        border-top: 1px solid var(--vt-border);
        background: #fff
    }

    #chat-input {
        flex: 1;
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        font-size: 14px;
        outline: none
    }

    #chat-input:focus {
        border-color: var(--vt-panel)
    }

    #chat-input-area button {
        background: var(--vt-panel);
        color: #fff;
        border: none;
        padding: 12px 18px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 700
    }

    #chat-input-area button:hover {
        background: var(--vt-panel-2)
    }

    #chatbot-container,
    #chat-icon {
        pointer-events: auto
    }

    #chat-messages::-webkit-scrollbar {
        width: 10px
    }

    #chat-messages::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 8px
    }

    #chat-messages::-webkit-scrollbar-track {
        background: transparent
    }

    @media (max-width:480px) {
        #chat-window {
            width: calc(100vw - 32px);
            height: 60vh
        }
    }
</style>

<!-- =========================================================
     4) JS — behavior + absolute endpoint to THIS file
     ========================================================= -->
<script>
    (function() {
        const ENDPOINT = '/customer/chatbot.php'; // always post to THIS file

        function showOptions(options) {
            const optionsContainer = document.getElementById('chat-options');
            optionsContainer.innerHTML = '';
            options.forEach(option => {
                const btn = document.createElement('button');
                btn.className = 'chat-option-btn';
                btn.textContent = option;
                btn.onclick = () => {
                    addUserMessage(option);
                    sendMessage(option);
                    optionsContainer.innerHTML = '';
                };
                optionsContainer.appendChild(btn);
            });
        }

        function addUserMessage(text) {
            const messages = document.getElementById('chat-messages');
            const el = document.createElement('p');
            el.className = 'user-msg';
            el.textContent = text;
            messages.appendChild(el);
            messages.scrollTop = messages.scrollHeight;
        }

        function addBotMessage(html) {
            const messages = document.getElementById('chat-messages');
            const el = document.createElement('p');
            el.className = 'bot-msg';
            el.innerHTML = html;
            messages.appendChild(el);
            messages.scrollTop = messages.scrollHeight;
        }

        function toggleChat() {
            const chatWindow = document.getElementById('chat-window');
            const isOpen = chatWindow.style.display === 'flex';
            chatWindow.style.display = isOpen ? 'none' : 'flex';
            if (!isOpen) {
                showOptions(["Browse Watches", "Best Deals", "Find by Brand", "Track Order", "Customer Support"]);
            }
        }

        function sendMessage(message) {
            const input = document.getElementById('chat-input');
            const msg = message || input.value.trim();
            if (!msg) return;

            addUserMessage(msg);
            if (!message) input.value = '';

            fetch(ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'message=' + encodeURIComponent(msg)
                })
                .then(res => res.json())
                .then(data => {
                    addBotMessage(data.message || 'No response.');
                    if (Array.isArray(data.options) && data.options.length) {
                        showOptions(data.options);
                    }
                })
                .catch(() => addBotMessage("Oops! Something went wrong."));
        }

        // expose + guarantee click even if inline onclick is blocked
        window.toggleChat = toggleChat;
        window.sendMessage = sendMessage;
        document.addEventListener('DOMContentLoaded', () => {
            const icon = document.getElementById('chat-icon');
            if (icon) icon.addEventListener('click', toggleChat);
        });
    })();
</script>