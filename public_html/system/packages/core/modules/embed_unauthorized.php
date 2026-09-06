<?php
/**
 * Shown when an iframe (?embed=1) requests a page the current user cannot open.
 * Must not call Core::redirectTo() — that uses window.open(..., "_top") and
 * yanks the parent dashboard off the tab the operator just opened.
 */
use system\classes\Core;
use system\classes\Configuration;

$embed_page = isset($embed_denied_page) ? (string) $embed_denied_page : (string) Configuration::$PAGE;
$embed_tools = [
    'file-manager' => ['tab' => 'file_manager', 'name' => 'File Manager'],
    'portainer' => ['tab' => 'portainer', 'name' => 'Portainer'],
];
$tool = isset($embed_tools[$embed_page]) ? $embed_tools[$embed_page] : null;
$tool_name = $tool ? $tool['name'] : 'This tool';
$login_enabled = (bool) Core::getSetting('login_enabled', 'core');
$logged_in = Core::isUserLoggedIn();

if ($tool) {
    $return = 'robot/' . $tool['tab'];
} else {
    $return = 'robot';
}
$login_url = Core::getURL('login') . '?q=' . rawurlencode(base64_encode($return));

if ($logged_in) {
    $title = 'Not available for this account';
    $body = $tool_name . ' is limited to administrator and supervisor accounts.';
    $show_signin = false;
} elseif (!$login_enabled) {
    $title = 'Sign-in is turned off';
    $body = $tool_name . ' needs a signed-in account, but sign-in is disabled on this dashboard.';
    $show_signin = false;
} else {
    $title = 'Sign in to continue';
    $body = 'Sign in to use ' . $tool_name . '.';
    $show_signin = true;
}
?>
</head>
<body style="margin:0;background:#fff;color:#111827;font:13px/1.4 -apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;">
<div style="max-width:420px;margin:48px auto;padding:24px;border:1px solid #e6e8eb;border-radius:10px;text-align:center;">
    <p style="margin:0 0 8px;" aria-hidden="true">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2c5686" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="11" width="16" height="11" rx="2"></rect>
            <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
        </svg>
    </p>
    <h3 style="margin:0 0 8px;font-size:18px;font-weight:600;"><?php echo htmlspecialchars($title) ?></h3>
    <p style="margin:0 0 16px;color:#6b7280;"><?php echo htmlspecialchars($body) ?></p>
    <?php if ($show_signin) { ?>
        <a href="<?php echo htmlspecialchars($login_url) ?>" target="_top"
           style="display:inline-block;padding:8px 14px;background:#2c5686;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;">
            Sign in
        </a>
    <?php } ?>
</div>
</body>
</html>
