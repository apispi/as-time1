.auth { padding: 4rem 0; }
.auth-card {
    max-width: 26rem; margin: 0 auto; background: var(--surface);
    border: 1px solid var(--border); border-radius: 1rem; padding: 2rem;
}
.auth-card h1 { font-size: 1.6rem; margin-bottom: 0.4rem; }
.auth-card .sub { color: var(--muted); margin-bottom: 1.5rem; font-size: 0.95rem; }
.field { margin-bottom: 1rem; }
.field label { display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 0.35rem; }
.field input[type=text], .field input[type=email], .field input[type=password] {
    width: 100%; min-height: 44px; padding: 0.6rem 0.8rem; font: inherit; color: var(--text);
    background: var(--bg); border: 1px solid var(--surface-2); border-radius: 0.6rem;
}
.field input[aria-invalid=true] { border-color: var(--danger); }
.field .hint { color: var(--muted); font-size: 0.8rem; margin-top: 0.3rem; }
.error { color: var(--danger); font-size: 0.85rem; margin-top: 0.3rem; }
.alert { border: 1px solid rgba(252, 165, 165, 0.4); background: rgba(252, 165, 165, 0.08); color: var(--danger); border-radius: 0.6rem; padding: 0.7rem 0.9rem; font-size: 0.9rem; margin-bottom: 1rem; }
.check { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: var(--muted); margin-bottom: 1.25rem; }
.check input { width: 1.1rem; height: 1.1rem; accent-color: var(--accent); }
.divider { display: flex; align-items: center; gap: 0.75rem; color: var(--muted); font-size: 0.85rem; margin: 1.25rem 0; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
.btn-google { background: #fff; color: #1f1f1f; font-weight: 500; }
.btn-google:hover { background: #f1f5f9; }
.alt { text-align: center; color: var(--muted); font-size: 0.9rem; margin-top: 1.5rem; }
.alt a { color: var(--accent); }
