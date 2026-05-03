"use strict";

const PRODUCTS = [
    {sku:'SIGMA-PLUS', name:'SIGMA Plus', series:'SIGMA', price:1760, gst:18, inner:'1×1', master:'12', img:'sigma-plus.jpg', best:true},
    {sku:'SIGMA-BASE', name:'SIGMA', series:'SIGMA', price:1700, gst:5, inner:'1×1', master:'12', img:'sigma.jpg'},
    {sku:'SIGMA-R1-PLUS', name:'SIGMA R1 Plus', series:'SIGMA', price:1660, gst:18, inner:'1×1', master:'12', img:'sigma-r1-plus.jpg'},
    {sku:'SIGMA-R1', name:'SIGMA R1', series:'SIGMA', price:1600, gst:5, inner:'1×1', master:'12', img:'sigma-r1.jpg'},
    {sku:'AURA-PLUS', name:'AURA Plus', series:'AURA', price:1670, gst:18, inner:'1×1', master:'12', img:'aura-plus.jpg', best:true},
    {sku:'AURA-BASE', name:'AURA', series:'AURA', price:1610, gst:5, inner:'1×1', master:'12', img:'aura.jpg'},
    {sku:'AURA-R1-PLUS', name:'AURA R1 Plus', series:'AURA', price:1570, gst:18, inner:'1×1', master:'12', img:'aura-r1-plus.jpg'},
    {sku:'AURA-R1', name:'AURA R1', series:'AURA', price:1510, gst:5, inner:'1×1', master:'12', img:'aura-r1.jpg'},
    {sku:'EAGLE-PLUS', name:'EAGLE Plus', series:'EAGLE', price:1535, gst:18, inner:'1×1', master:'12', img:'eagle-plus.jpg', best:true},
    {sku:'EAGLE-BASE', name:'EAGLE', series:'EAGLE', price:1475, gst:5, inner:'1×1', master:'12', img:'eagle.jpg'},
    {sku:'EAGLE-R1-PLUS', name:'EAGLE R1 Plus', series:'EAGLE', price:1435, gst:18, inner:'1×1', master:'12', img:'eagle-r1-plus.jpg'},
    {sku:'EAGLE-R1', name:'EAGLE R1', series:'EAGLE', price:1375, gst:5, inner:'1×1', master:'12', img:'eagle-r1.jpg'},
    {sku:'ALEX-PLUS', name:'ALEX Plus', series:'ALEX', price:1595, gst:18, inner:'1×1', master:'12', img:'alex-plus.jpg', best:true},
    {sku:'ALEX-BASE', name:'ALEX', series:'ALEX', price:1535, gst:5, inner:'1×1', master:'12', img:'alex.jpg'},
    {sku:'ALEX-R1-PLUS', name:'ALEX R1 Plus', series:'ALEX', price:1495, gst:18, inner:'1×1', master:'12', img:'alex-r1-plus.jpg'},
    {sku:'ALEX-R1', name:'ALEX R1', series:'ALEX', price:1435, gst:5, inner:'1×1', master:'12', img:'alex-r1.jpg'},
    {sku:'ECOTECH-PLUS', name:'ECOTECH Plus', series:'ECOTECH', price:1430, gst:18, inner:'1×1', master:'12', img:'ecotech-plus.jpg'},
    {sku:'ECOTECH-BASE', name:'ECOTECH', series:'ECOTECH', price:1370, gst:5, inner:'1×1', master:'12', img:'ecotech.jpg'},
    {sku:'ECOTECH-R1-PLUS', name:'ECOTECH R1 Plus', series:'ECOTECH', price:1320, gst:18, inner:'1×1', master:'12', img:'ecotech-r1-plus.jpg'},
    {sku:'ECOTECH-R1', name:'ECOTECH R1', series:'ECOTECH', price:1270, gst:5, inner:'1×1', master:'12', img:'ecotech-r1.jpg'},
    {sku:'RAMBO-333-PLUS', name:'RAMBO 333 Plus', series:'RAMBO', price:1240, gst:18, inner:'3×4', master:'12', img:'rambo-333-plus.jpg', best:true},
    {sku:'RAMBO-333', name:'RAMBO 333', series:'RAMBO', price:1180, gst:5, inner:'3×4', master:'12', img:'rambo-333.jpg'},
    {sku:'RAMBO-333-R1-PLUS', name:'RAMBO 333 R1 Plus', series:'RAMBO', price:1160, gst:18, inner:'3×4', master:'12', img:'rambo-333-r1-plus.jpg'},
    {sku:'RAMBO-333-R1', name:'RAMBO 333 R1', series:'RAMBO', price:1100, gst:5, inner:'3×4', master:'12', img:'rambo-333-r1.jpg'},
    {sku:'RAMBO-111-PLUS', name:'RAMBO 111 Plus', series:'RAMBO', price:680, gst:18, inner:'6×4', master:'24', img:'rambo-111-plus.jpg'},
    {sku:'RAMBO-111', name:'RAMBO 111', series:'RAMBO', price:620, gst:5, inner:'6×4', master:'24', img:'rambo-111.jpg', best:true},
    {sku:'RAMBO-100-PLUS', name:'RAMBO 100 Plus', series:'RAMBO', price:620, gst:18, inner:'6×4', master:'24', img:'rambo-100-plus.jpg'},
    {sku:'RAMBO-100', name:'RAMBO 100', series:'RAMBO', price:560, gst:5, inner:'6×4', master:'24', img:'rambo-100.jpg'},
    {sku:'LEON-R1-GREY-PLUS', name:'LEON R1 Grey Plus', series:'LEON', price:1200, gst:18, inner:'1×1 / 3×4', master:'15 / 12', img:'leon-r1-grey-plus.jpg'},
    {sku:'LEON-R1-GREY', name:'LEON R1 Grey', series:'LEON', price:1140, gst:5, inner:'1×1 / 3×4', master:'15 / 12', img:'leon-r1-grey.jpg'},
    {sku:'LION-R1-PLUS', name:'LION R1 Plus', series:'LION', price:1140, gst:18, inner:'1×1 / 3×4', master:'15 / 12', img:'lion-r1-plus.jpg'},
    {sku:'LION-R1', name:'LION R1', series:'LION', price:1070, gst:5, inner:'1×1 / 3×4', master:'15 / 12', img:'lion-r1.jpg'},
    {sku:'CHARLIE-R1-PLUS', name:'CHARLIE R1 Plus', series:'CHARLIE', price:1020, gst:18, inner:'1×1 / 4×4', master:'15 / 16', img:'charlie-r1-plus.jpg', best:true},
    {sku:'CHARLIE-R1', name:'CHARLIE R1', series:'CHARLIE', price:970, gst:5, inner:'1×1 / 4×4', master:'15 / 16', img:'charlie-r1.jpg'},
    {sku:'CHARLIE-PLUS', name:'CHARLIE Plus', series:'CHARLIE', price:820, gst:18, inner:'1×1 / 5×4', master:'15 / 20', img:'charlie-plus.jpg'},
    {sku:'CHARLIE-BASE', name:'CHARLIE', series:'CHARLIE', price:770, gst:5, inner:'1×1 / 5×4', master:'15 / 20', img:'charlie.jpg'},
    {sku:'EMMA-R1-PLUS', name:'EMMA R1 Plus', series:'EMMA', price:1100, gst:18, inner:'1×1 / 4×4', master:'15 / 16', img:'emma-r1-plus.jpg'},
    {sku:'EMMA-R1', name:'EMMA R1', series:'EMMA', price:1050, gst:5, inner:'1×1 / 4×4', master:'15 / 16', img:'emma-r1.jpg'},
    {sku:'EMMA-PLUS', name:'EMMA Plus', series:'EMMA', price:880, gst:18, inner:'1×1 / 5×4', master:'15 / 20', img:'emma-plus.jpg'},
    {sku:'EMMA-BASE', name:'EMMA', series:'EMMA', price:830, gst:5, inner:'1×1 / 5×4', master:'15 / 20', img:'emma.jpg'},
    {sku:'HULK-PRO-PLUS', name:'HULK Pro Plus', series:'HULK', price:870, gst:18, inner:'1×1 / 4×4', master:'15 / 16', img:'hulk-pro-plus.jpg'},
    {sku:'HULK-PRO', name:'HULK Pro', series:'HULK', price:820, gst:5, inner:'1×1 / 4×4', master:'15 / 16', img:'hulk-pro.jpg'},
    {sku:'HULK-PLUS', name:'HULK Plus', series:'HULK', price:850, gst:18, inner:'1×1 / 4×4', master:'15 / 16', img:'hulk-plus.jpg'},
    {sku:'HULK-BASE', name:'HULK', series:'HULK', price:800, gst:5, inner:'1×1 / 4×4', master:'15 / 16', img:'hulk.jpg'},
];

function el(tag, opts) {
    const e = document.createElement(tag);
    if (opts) {
        if (opts.cls) e.className = opts.cls;
        if (opts.text) e.textContent = opts.text;
        if (opts.attrs) for (const k in opts.attrs) e.setAttribute(k, opts.attrs[k]);
    }
    return e;
}

function getIcon(id) {
    const tpl = document.getElementById(id);
    return tpl ? tpl.content.cloneNode(true) : null;
}

function buildBadge(gst) {
    const isMusical = gst === 18;
    const wrap = el('span', { cls: 'badge ' + (isMusical ? 'musical' : 'normal') });
    const ico = getIcon(isMusical ? 'icon-music' : 'icon-leaf');
    if (ico) wrap.appendChild(ico);
    wrap.appendChild(el('span', { text: isMusical ? 'Musical' : 'Normal' }));
    return wrap;
}

function buildProductCard(p) {
    const card = el('div', { cls: 'prod-card' });

    const imgWrap = el('div', { cls: 'img-wrap' });
    imgWrap.appendChild(buildBadge(p.gst));
    if (p.best) {
        imgWrap.appendChild(el('span', { cls: 'badge bestseller', text: '★ Bestseller' }));
    }
    const img = el('img', { attrs: { src: 'products/' + p.img, alt: p.name, loading: 'lazy' } });
    imgWrap.appendChild(img);

    const body = el('div', { cls: 'body' });
    body.appendChild(el('span', { cls: 'series-pill', text: p.series + ' Series' }));
    body.appendChild(el('h3', { text: p.name }));
    body.appendChild(el('div', { cls: 'sku', text: 'SKU · ' + p.sku }));

    const meta = el('div', { cls: 'meta' });
    const item1 = el('div', { cls: 'meta-item' });
    item1.appendChild(el('span', { cls: 'k', text: 'Pack' }));
    item1.appendChild(el('span', { cls: 'v', text: p.inner + ' / ' + p.master }));
    const item2 = el('div', { cls: 'meta-item' });
    item2.appendChild(el('span', { cls: 'k', text: 'GST' }));
    item2.appendChild(el('span', { cls: 'v', text: p.gst + '%  · HSN ' + (p.gst === 18 ? '9503' : '8712') }));
    meta.appendChild(item1); meta.appendChild(item2);
    body.appendChild(meta);

    const priceLine = el('div', { cls: 'price-line' });
    priceLine.appendChild(el('span', { cls: 'from', text: 'From' }));
    priceLine.appendChild(el('span', { cls: 'price', text: '₹' + p.price.toLocaleString('en-IN') }));
    priceLine.appendChild(el('span', { cls: 'price-note', text: 'wholesale · login for tier' }));
    body.appendChild(priceLine);

    // Quote button → real WhatsApp link with product context (works on static preview AND live WP)
    const waMsg = 'Hi Luvron, I would like a wholesale quote for *' + p.name + '* (SKU: ' + p.sku + '). Please share dealer pricing, dispatch lead-time, and freight to my location.';
    const waUrl = 'https://wa.me/919212389139?text=' + encodeURIComponent(waMsg);
    const btn = el('a', { cls: 'quote-btn', attrs: { href: waUrl, target: '_blank', rel: 'noopener' } });
    const waIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    waIcon.setAttribute('viewBox', '0 0 24 24');
    waIcon.setAttribute('fill', 'currentColor');
    const waPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    waPath.setAttribute('d', 'M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2-.2-.3 0-.5.1-.6.1-.1.3-.4.5-.5.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.1-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4s-1 1-1 2.5 1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.4zM12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5-1.3c1.5.8 3.2 1.3 5 1.3 5.5 0 10-4.5 10-10S17.5 2 12 2z');
    waIcon.appendChild(waPath);
    btn.appendChild(waIcon);
    btn.appendChild(document.createTextNode('Request Quote on WhatsApp'));
    body.appendChild(btn);

    card.appendChild(imgWrap);
    card.appendChild(body);
    return card;
}

// Render bestseller grid on home page
function renderBestsellers() {
    const grid = document.getElementById('bestseller-grid');
    if (!grid) return;
    const best = PRODUCTS.filter(p => p.best).slice(0, 8);
    while (grid.firstChild) grid.removeChild(grid.firstChild);
    best.forEach(p => grid.appendChild(buildProductCard(p)));
}

// Render full product grid on products page (with filters)
function renderProductGrid() {
    const grid = document.getElementById('prod-grid');
    if (!grid) return;

    const params = new URLSearchParams(location.search);
    let activeSeries = params.get('series') || 'all';
    let activeTier = params.get('tier') || 'all';
    let activeSort = 'default';
    let activeQuery = '';

    function update() {
        let list = PRODUCTS.slice();
        if (activeSeries !== 'all') list = list.filter(p => p.series === activeSeries);
        if (activeTier === 'musical') list = list.filter(p => p.gst === 18);
        if (activeTier === 'normal')  list = list.filter(p => p.gst === 5);
        if (activeQuery) {
            const q = activeQuery.toLowerCase();
            list = list.filter(p => (p.name + ' ' + p.sku + ' ' + p.series).toLowerCase().includes(q));
        }
        if (activeSort === 'price-low') list.sort((a, b) => a.price - b.price);
        else if (activeSort === 'price-high') list.sort((a, b) => b.price - a.price);
        else if (activeSort === 'name') list.sort((a, b) => a.name.localeCompare(b.name));

        while (grid.firstChild) grid.removeChild(grid.firstChild);
        list.forEach(p => grid.appendChild(buildProductCard(p)));

        const counter = document.getElementById('result-count');
        if (counter) counter.textContent = list.length;
    }

    document.querySelectorAll('.chip[data-series]').forEach(c => {
        if (c.dataset.series === activeSeries) c.classList.add('active');
        c.addEventListener('click', () => {
            document.querySelectorAll('.chip[data-series]').forEach(x => x.classList.remove('active'));
            c.classList.add('active');
            activeSeries = c.dataset.series;
            update();
        });
    });
    document.querySelectorAll('.chip[data-tier]').forEach(c => {
        if (c.dataset.tier === activeTier) c.classList.add('active');
        c.addEventListener('click', () => {
            document.querySelectorAll('.chip[data-tier]').forEach(x => x.classList.remove('active'));
            c.classList.add('active');
            activeTier = c.dataset.tier;
            update();
        });
    });
    const sortSel = document.getElementById('sort-by');
    if (sortSel) sortSel.addEventListener('change', () => { activeSort = sortSel.value; update(); });

    const search = document.getElementById('search-input');
    if (search) search.addEventListener('input', () => { activeQuery = search.value.trim(); update(); });

    update();
}

// Mobile nav
function setupMobileNav() {
    const btn = document.getElementById('mobile-toggle-btn');
    const nav = document.getElementById('nav');
    if (btn && nav) btn.addEventListener('click', () => nav.classList.toggle('open'));
}

// Scroll reveal
function setupReveal() {
    const io = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.section-head, .feature-card, .tier-card, .testimonial, .cat-card, .stat-cell').forEach(el => {
        el.classList.add('reveal');
        io.observe(el);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    renderBestsellers();
    renderProductGrid();
    setupMobileNav();
    setupReveal();
});
