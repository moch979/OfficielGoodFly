<?php

require __DIR__ . '/_init.php';

use_design_draft();
$state = designer_state();
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>المصمم المرئي | Good Fly</title>
    <link rel="icon" href="../favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; overflow: hidden; font-family: Tajawal, Tahoma, sans-serif; background: #0F2350; color: #1D3C78; }
        button, input, select, textarea { font-family: inherit; }
        .top { min-height: 58px; display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 14px; color: #F4F8FC; flex-wrap: wrap; }
        .top b { color: #F2C14E; letter-spacing: .4px; }
        .top a { color: #F8DC8E; text-decoration: none; font-size: 13px; }
        .cluster { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .top button, .top select {
            background: transparent; color: #F4F8FC; border: 1px solid rgba(242,193,78,.45);
            border-radius: 999px; padding: 6px 10px; cursor: pointer; font-size: 13px;
        }
        .top select option { color: #1D3C78; }
        .top button.on { background: #F2C14E; color: #0F2350; border-color: #F2C14E; font-weight: 800; }
        .draftbar { display: none; align-items: center; gap: 6px; background: rgba(242,193,78,.14); border: 1px solid rgba(242,193,78,.5); border-radius: 999px; padding: 4px 6px 4px 12px; font-size: 13px; }
        .draftbar.show { display: flex; }
        .top .publish { background: #F2C14E; color: #0F2350; font-weight: 800; border-color: #F2C14E; }
        .shell { height: calc(100% - 58px); display: flex; min-height: 0; }
        .panel { width: 410px; background: #F4F8FC; overflow: auto; border-left: 1px solid rgba(242,193,78,.4); }
        .stage { flex: 1; background: #c9d4e3; display: flex; justify-content: center; min-width: 0; }
        iframe { width: 100%; height: 100%; border: 0; background: #fff; box-shadow: 0 0 40px rgba(15,35,80,.18); transition: width .25s; }
        .tabs { display: flex; gap: 6px; padding: 12px; position: sticky; top: 0; background: #F4F8FC; z-index: 3; }
        .tabs button { flex: 1; border: 1px solid #dbe4f0; background: #fff; border-radius: 999px; padding: 8px; cursor: pointer; font-weight: 700; }
        .tabs button.on { background: #1D3C78; color: #F4F8FC; }
        .pane { padding: 0 14px 28px; }
        .status { min-height: 18px; font-size: 12px; color: #3F4E66; padding: 0 14px 8px; }
        .status.err { color: #8d2f2f; font-weight: 700; }
        label { display: block; font-weight: 800; font-size: 13px; margin: 12px 0 6px; }
        input[type=text], input[type=number], input[type=password], textarea, select {
            width: 100%; padding: 10px 12px; border: 1px solid #d9e1ec; border-radius: 12px; background: #fff; color: #1D3C78; font-size: 14px;
        }
        textarea { min-height: 88px; resize: vertical; }
        .hint { color: #66708a; font-size: 12px; margin-top: 4px; }
        .ref { font-size: 12px; color: #66708a; background: #f6f9fd; border: 1px dashed #dbe4f0; border-radius: 8px; padding: 6px 8px; margin-bottom: 6px; white-space: pre-line; max-height: 80px; overflow: auto; }
        .lang-tag { display: inline-block; font-size: 11px; background: #F8DC8E; border-radius: 6px; padding: 1px 6px; margin-inline-start: 6px; }
        .missing { color: #8d2f2f; font-size: 11px; font-weight: 700; margin-inline-start: 6px; }
        .colors { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .swatch { background: #fff; border: 1px solid #dbe4f0; border-radius: 14px; padding: 8px; margin: 0; }
        .swatch input { width: 100%; height: 36px; border: 0; background: transparent; padding: 0; margin-top: 6px; }
        .check { display: flex; align-items: center; gap: 8px; font-weight: 700; margin: 8px 0; }
        .check input { width: auto; }
        .btn { border: 0; border-radius: 999px; padding: 10px 14px; font-weight: 800; cursor: pointer; background: linear-gradient(135deg,#F2C14E,#E3A93A); color: #0F2350; }
        .btn.tiny { padding: 6px 10px; font-size: 12px; background: #fff; border: 1px solid #dbe4f0; }
        .btn.danger { background: #8d2f2f; color: #fff; }
        .btn.ghost { background: #fff; border: 1px solid #dbe4f0; }
        .order { display: flex; flex-direction: column; gap: 6px; }
        .order > div, .item { display: flex; justify-content: space-between; gap: 8px; align-items: center; background: #fff; border: 1px solid #dbe4f0; border-radius: 12px; padding: 8px 10px; flex-wrap: wrap; }
        .item.off { opacity: .6; }
        .item span { flex: 1; min-width: 120px; font-size: 13px; }
        h2 { font-size: 20px; margin: 14px 0 8px; }
        h3 { font-size: 16px; margin: 20px 0 6px; }
        .intro { color: #3F4E66; font-size: 13px; margin: 0 0 8px; }
        .preview { width: 100%; height: 92px; object-fit: cover; border-radius: 10px; margin-top: 8px; }
        .actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 12px; }
        .deprow { display: grid; grid-template-columns: 1fr 1fr 80px auto; gap: 4px; margin-bottom: 4px; }
        .deprow input { padding: 6px; border: 1px solid #d9e1ec; border-radius: 8px; width: 100%; }
        .hotelbox { border: 1px solid #dbe4f0; border-radius: 10px; padding: 8px; margin-bottom: 6px; background: #fbfdff; display: grid; gap: 4px; }
        .hotelbox input { padding: 6px; border: 1px solid #d9e1ec; border-radius: 8px; width: 100%; font-family: inherit; }
        .hotelprices { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; }
        .thumbs { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; }
        .thumbs img { width: 56px; height: 40px; object-fit: cover; border-radius: 6px; }
        .formsec { margin: 18px 0 0; padding-top: 10px; border-top: 1px solid #dbe4f0; font-size: 14px; }
        .itemform { background: #fff; border: 2px solid #F2C14E; border-radius: 14px; padding: 4px 12px 12px; margin: 8px 0; }
        .editlang { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; font-size: 12px; font-weight: 700; margin: 6px 0; }
        .editlang button { border: 1px solid #dbe4f0; background: #fff; border-radius: 999px; padding: 4px 10px; cursor: pointer; font-weight: 700; }
        .editlang button.on { background: #1D3C78; color: #fff; border-color: #1D3C78; }
        body[data-edit-lang="ar"] .lang-pane:not([data-lang="ar"]),
        body[data-edit-lang="fr"] .lang-pane:not([data-lang="fr"]),
        body[data-edit-lang="en"] .lang-pane:not([data-lang="en"]) { display: none; }
        @media (max-width: 900px) {
            .shell { display: block; overflow: auto; height: auto; }
            .panel, .stage { width: auto; height: 70vh; }
            html, body { overflow: auto; }
        }
    </style>
</head>
<body data-edit-lang="ar">
    <header class="top">
        <div class="cluster">
            <b>GOOD FLY</b>
            <span>المصمم المرئي</span>
        </div>
        <div class="cluster">
            <button type="button" class="device on" data-width="100%" title="حاسوب"><i class="fa-solid fa-desktop"></i></button>
            <button type="button" class="device" data-width="820px" title="لوحي"><i class="fa-solid fa-tablet-screen-button"></i></button>
            <button type="button" class="device" data-width="390px" title="جوال"><i class="fa-solid fa-mobile-screen"></i></button>
            <select id="preview-lang" aria-label="لغة المعاينة والتحرير">
                <?php foreach (languages() as $code => $info): ?>
                <option value="<?= h($code) ?>"><?= h($info['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="pick-toggle" class="on">وضع التحديد</button>
        </div>
        <div class="cluster">
            <div class="draftbar<?= $state['hasDraft'] ? ' show' : '' ?>" id="draftbar">
                <span>تصميم غير منشور</span>
                <button type="button" class="publish" id="publish">نشر</button>
                <button type="button" id="discard">تجاهل</button>
            </div>
            <a href="index.php">لوحة التحكم</a>
            <a href="../index.php" target="_blank" rel="noopener">الموقع</a>
        </div>
    </header>
    <div class="shell">
        <aside class="panel">
            <div class="tabs">
                <button type="button" class="on" data-tab="design">التصميم</button>
                <button type="button" data-tab="content">المحتوى</button>
            </div>
            <div class="status" id="panel-status" role="status">تعديلات التصميم تُحفظ في مسودة لا يراها الزوار حتى تضغط «نشر». تعديلات المحتوى تُنشر عند الحفظ.</div>
            <div class="pane" id="pane-design">
                <form id="design-form">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="save_design">
                    <input type="hidden" name="section_order" id="section-order" value="<?= h(implode(',', $state['order'])) ?>">
                    <h2>الألوان</h2>
                    <div class="colors">
                        <?php foreach ($state['colors'] as $key => $color): ?>
                        <label class="swatch"><?= h($color['label']) ?>
                            <input type="color" name="colors[<?= h($key) ?>]" value="<?= h($color['value']) ?>" data-color="<?= h($key) ?>">
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <h2>الخطوط والمقاسات</h2>
                    <label for="body-font">خط النصوص</label>
                    <select name="body_font" id="body-font">
                        <?php foreach ($state['fonts'] as $name => $label): ?>
                        <option value="<?= h($name) ?>"<?= $state['bodyFont'] === $name ? ' selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="title-font">خط العناوين</label>
                    <select name="title_font" id="title-font">
                        <?php foreach ($state['fonts'] as $name => $label): ?>
                        <option value="<?= h($name) ?>"<?= $state['titleFont'] === $name ? ' selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="section-padding">فراغ الأقسام: <span id="pad-label"><?= (int) $state['sectionPadding'] ?></span>px</label>
                    <input type="range" name="section_padding" id="section-padding" min="48" max="160" value="<?= (int) $state['sectionPadding'] ?>">
                    <label for="hero-min">ارتفاع الواجهة: <span id="hero-label"><?= (int) $state['heroMin'] ?></span>%</label>
                    <input type="range" name="hero_min_height" id="hero-min" min="50" max="100" value="<?= (int) $state['heroMin'] ?>">
                    <h2>إظهار الأقسام</h2>
                    <?php foreach ($state['flags'] as $key => $flag): ?>
                    <label class="check"><input type="checkbox" name="flags[<?= h($key) ?>]" value="1" data-flag="<?= h($key) ?>"<?= $flag['on'] ? ' checked' : '' ?>> <?= h($flag['label']) ?></label>
                    <?php endforeach; ?>
                    <h2>ترتيب الأقسام</h2>
                    <p class="intro">الشريط والشعار والتذييل ثابتة في أماكنها، وهذه الأقسام تتحرك بينها.</p>
                    <div class="order" id="order-list"></div>
                    <h2>استرجاع</h2>
                    <div class="actions">
                        <button type="button" class="btn ghost" id="reset-design">استعادة التصميم الأصلي</button>
                        <button type="button" class="btn ghost" id="make-snapshot">حفظ نقطة استرجاع</button>
                        <a class="btn ghost" href="snapshots.php" style="text-decoration:none">كل النقاط</a>
                    </div>
                </form>
            </div>
            <div class="pane" id="pane-content" hidden>
                <label for="section-jump">القسم</label>
                <select id="section-jump">
                    <?php foreach ($state['sections'] as $id => $label): ?>
                    <option value="<?= h($id) ?>"><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="editlang" id="edit-lang"><span>لغة التحرير:</span>
                    <?php foreach (languages() as $code => $info): ?>
                    <button type="button" data-lang="<?= h($code) ?>"><?= h($info['name']) ?></button>
                    <?php endforeach; ?>
                </div>
                <div id="section-editor"><p class="intro">اضغط أي قسم في المعاينة، أو اختره من القائمة.</p></div>
            </div>
        </aside>
        <div class="stage">
            <iframe id="preview" title="معاينة الموقع"></iframe>
        </div>
    </div>
    <script>
        const csrf = <?= json_for_script($csrf) ?>;
        const stacks = <?= json_for_script($state['fontStacks']) ?>;
        const flagZones = <?= json_for_script($state['flagZones']) ?>;
        const orderLabels = <?= json_for_script($state['orderLabels']) ?>;
        const langNames = <?= json_for_script(array_map(static function ($info) { return $info['name']; }, languages())) ?>;
        const siteBase = <?= json_for_script(base_path()) ?>;
        const colorVars = {
            color_green: '--gf-green', color_darkgreen: '--gf-darkgreen', color_gold: '--gf-gold',
            color_lightgold: '--gf-lightgold', color_ivory: '--gf-ivory', color_lightivory: '--gf-lightivory', color_slate: '--gf-slate'
        };
        const frame = document.getElementById('preview');
        const status = document.getElementById('panel-status');
        const draftbar = document.getElementById('draftbar');
        let picking = true;
        let current = null;
        let previewLang = localStorage.getItem('gfEditLang') || 'ar';
        let order = <?= json_for_script($state['order']) ?>;
        let designTimer = null;
        let designSaving = false;
        let designAgain = false;

        function setStatus(message, isError) {
            status.textContent = message || '';
            status.classList.toggle('err', !!isError);
        }
        function doc() { return frame.contentDocument; }
        function channels(hex) {
            const v = hex.replace('#', '');
            return parseInt(v.slice(0, 2), 16) + ' ' + parseInt(v.slice(2, 4), 16) + ' ' + parseInt(v.slice(4, 6), 16);
        }
        function previewUrl() {
            return siteBase + '/index.php?designer=1&lang=' + encodeURIComponent(previewLang) + '&v=' + Date.now();
        }
        function reloadPreview() {
            const y = doc() && doc().defaultView ? doc().defaultView.scrollY : 0;
            frame.dataset.restoreY = String(y);
            frame.src = previewUrl();
        }
        function setEditLang(lang) {
            previewLang = lang;
            localStorage.setItem('gfEditLang', lang);
            document.body.dataset.editLang = lang;
            document.getElementById('preview-lang').value = lang;
            document.querySelectorAll('#edit-lang button').forEach((b) => b.classList.toggle('on', b.dataset.lang === lang));
        }

        function applyLive() {
            const d = doc();
            if (!d || !d.documentElement) return;
            const root = d.documentElement;
            document.querySelectorAll('[data-color]').forEach((input) => root.style.setProperty(colorVars[input.dataset.color], channels(input.value)));
            root.style.setProperty('--gf-body', stacks[document.getElementById('body-font').value] || stacks.Tajawal);
            root.style.setProperty('--gf-title', stacks[document.getElementById('title-font').value] || stacks.Amiri);
            root.style.setProperty('--gf-section-y', document.getElementById('section-padding').value + 'px');
            root.style.setProperty('--gf-hero-min', document.getElementById('hero-min').value + 'vh');
            order.forEach((id, index) => {
                const el = d.querySelector('.gf-flow > [data-gf="' + id + '"]');
                if (el) el.style.order = String(index + 1);
            });
            document.querySelectorAll('[data-flag]').forEach((input) => {
                const on = input.checked;
                const zone = flagZones[input.dataset.flag];
                if (zone) d.querySelectorAll('[data-gf="' + zone + '"]').forEach((el) => el.classList.toggle('gf-off', !on));
                d.querySelectorAll('[data-gf-flag="' + input.dataset.flag + '"]').forEach((el) => el.classList.toggle('gf-off', !on));
            });
        }
        function tellFrame(scroll) {
            if (!frame.contentWindow) return;
            frame.contentWindow.postMessage({ type: 'gf-pick', on: picking }, location.origin);
            if (current) frame.contentWindow.postMessage({ type: 'gf-focus', section: current, scroll: !!scroll }, location.origin);
        }

        async function post(fd) {
            if (!fd.has('csrf')) fd.append('csrf', csrf);
            const response = await fetch('designer_api.php', { method: 'POST', body: fd });
            let data = null;
            try { data = await response.json(); } catch (error) { data = null; }
            if (!data || !data.ok) throw new Error((data && data.message) || 'تعذر الحفظ');
            return data;
        }
        function showDraft(on) { draftbar.classList.toggle('show', !!on); }

        function scheduleDesign() {
            applyLive();
            designAgain = true;
            clearTimeout(designTimer);
            designTimer = setTimeout(flushDesign, 500);
        }
        async function flushDesign() {
            if (designSaving) return;
            designSaving = true;
            designAgain = false;
            try {
                const data = await post(new FormData(document.getElementById('design-form')));
                setStatus(data.message);
                showDraft(true);
            } catch (error) {
                setStatus(error.message, true);
            } finally {
                designSaving = false;
                if (designAgain) scheduleDesign();
            }
        }
        async function simpleAction(action, confirmText) {
            if (confirmText && !confirm(confirmText)) return;
            const fd = new FormData();
            fd.append('action', action);
            try {
                const data = await post(fd);
                setStatus(data.message);
                if ('draft' in data) showDraft(data.draft);
                if (data.reload) location.reload();
                else if (action === 'publish_design') reloadPreview();
            } catch (error) {
                setStatus(error.message, true);
            }
        }

        function renderOrder() {
            const box = document.getElementById('order-list');
            box.innerHTML = '';
            order.forEach((id, index) => {
                const row = document.createElement('div');
                const name = document.createElement('span');
                name.textContent = orderLabels[id] || id;
                const actions = document.createElement('div');
                actions.className = 'actions';
                actions.style.marginTop = '0';
                [['أعلى', -1, index === 0], ['أسفل', 1, index === order.length - 1]].forEach(([text, step, disabled]) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'btn tiny';
                    b.textContent = text;
                    b.disabled = disabled;
                    b.addEventListener('click', () => moveSection(index, step));
                    actions.append(b);
                });
                row.append(name, actions);
                box.append(row);
            });
            document.getElementById('section-order').value = order.join(',');
        }
        function moveSection(index, step) {
            const next = index + step;
            if (!order[next]) return;
            const copy = order.slice();
            [copy[index], copy[next]] = [copy[next], copy[index]];
            order = copy;
            renderOrder();
            scheduleDesign();
        }

        function mediaUrl(value) {
            if (!value) return '';
            if (/^(https?:)?\/\//i.test(value) || value.startsWith('/') || value.startsWith('data:')) return value;
            return siteBase + '/' + value.replace(/^\.\//, '');
        }
        function el(tag, attrs, text) {
            const node = document.createElement(tag);
            Object.entries(attrs || {}).forEach(([k, v]) => { if (v !== undefined && v !== null) node.setAttribute(k, v); });
            if (text !== undefined) node.textContent = text;
            return node;
        }

        /** nameFor(lang) gives the input name; values = {ar, fr, en}. */
        function departuresControl(field, rows) {
            const wrap = el('div');
            wrap.append(el('label', {}, field.label));
            const list = el('div');
            const addRow = (row) => {
                const line = el('div', { class: 'deprow' });
                const id = el('input', { type: 'hidden', name: 'dep_id[]', value: String(row.id || 0) });
                const date = el('input', { type: 'date', name: 'dep_date[]', title: 'تاريخ الانطلاق' });
                date.value = row.depart_date || '';
                const back = el('input', { type: 'date', name: 'dep_return[]', title: 'تاريخ العودة' });
                back.value = row.return_date || '';
                const seats = el('input', { type: 'number', name: 'dep_seats[]', min: '0', placeholder: 'غير محدد', title: 'المقاعد (اختياري)' });
                seats.value = row.seats_left ?? '';
                const remove = smallButton('×', () => line.remove(), true);
                line.append(id, date, back, seats, remove);
                list.append(line);
            };
            (rows || []).forEach(addRow);
            const add = smallButton('إضافة موعد', () => addRow({}));
            wrap.append(el('div', { class: 'hint' }, 'الانطلاق* — العودة* — المقاعد (اختياري)'), list, add);
            if (field.hint) wrap.append(el('div', { class: 'hint' }, field.hint));
            return wrap;
        }

        const hotelTexts = [['madinah_hotel', 'فندق المدينة المنورة'], ['makkah_hotel', 'فندق مكة المكرمة'], ['makkah_note', 'المسافة أو النقل في مكة'], ['meals', 'الوجبات']];
        const roomNames = [['double', 'ثنائية'], ['triple', 'ثلاثية'], ['quad', 'رباعية'], ['quint', 'خماسية']];
        function hotelsControl(field, rows) {
            const wrap = el('div');
            wrap.append(el('label', {}, field.label + ' *'), el('input', { type: 'hidden', name: 'hotels_form', value: '1' }));
            const list = el('div');
            const addRow = (row) => {
                const box = el('div', { class: 'hotelbox' });
                box.append(el('input', { type: 'hidden', name: 'hotel_id[]', value: String(row.id || 0) }));
                hotelTexts.forEach(([column, label]) => ['ar', 'fr', 'en'].forEach((lang) => {
                    const key = lang === 'ar' ? column : column + '_' + lang;
                    const pane = el('div', { class: 'lang-pane', 'data-lang': lang });
                    const input = el('input', { type: 'text', name: 'hotel_' + key + '[]', placeholder: label + (lang === 'ar' ? '' : ' — ' + langNames[lang]), dir: lang === 'ar' ? 'rtl' : 'ltr' });
                    input.value = row[key] || '';
                    pane.append(input);
                    box.append(pane);
                }));
                const prices = el('div', { class: 'hotelprices' });
                roomNames.forEach(([type, label]) => {
                    const input = el('input', { type: 'number', min: '0', name: 'hotel_price_' + type + '[]', placeholder: label, title: label + ' (درهم)' });
                    const price = Number(row['price_' + type] || 0);
                    input.value = price > 0 ? String(price) : '';
                    prices.append(input);
                });
                box.append(prices, smallButton('إزالة الفندق', () => box.remove(), true));
                list.append(box);
            };
            (rows && rows.length ? rows : [{}]).forEach(addRow);
            wrap.append(list, smallButton('إضافة فندق', () => addRow({})));
            if (field.hint) wrap.append(el('div', { class: 'hint' }, field.hint));
            return wrap;
        }

        function fieldControl(field, nameFor, values, fileName) {
            const wrap = el('div');
            const type = field.type;
            if (field.section) wrap.append(el('h4', { class: 'formsec' }, field.section));
            if (type === 'departures') {
                wrap.append(departuresControl(field, values.ar));
                return wrap;
            }
            if (type === 'hotels') {
                wrap.append(hotelsControl(field, values.ar));
                return wrap;
            }
            if (type === 'gallery') {
                wrap.append(el('label', {}, field.label));
                const area = el('textarea', { name: nameFor('ar'), dir: 'ltr' });
                area.value = values.ar || '';
                const thumbs = el('div', { class: 'thumbs' });
                String(values.ar || '').split(/\n/).map((s) => s.trim()).filter(Boolean).forEach((path) => thumbs.append(el('img', { src: mediaUrl(path), alt: '' })));
                wrap.append(area, el('input', { type: 'file', name: 'file_gallery[]', multiple: 'multiple', accept: 'image/jpeg,image/png,image/webp,image/gif', style: 'margin-top:8px' }), thumbs);
                if (field.hint) wrap.append(el('div', { class: 'hint' }, field.hint));
                return wrap;
            }
            if (type === 'checkbox' || type === 'bool') {
                const check = el('label', { class: 'check' });
                const input = el('input', { type: 'checkbox', name: nameFor('ar'), value: '1' });
                input.checked = String(values.ar) === '1';
                check.append(input, document.createTextNode(' ' + field.label));
                wrap.append(check);
                if (field.hint) wrap.append(el('div', { class: 'hint' }, field.hint));
                return wrap;
            }
            const langs = field.translatable ? ['ar', 'fr', 'en'] : ['ar'];
            langs.forEach((lang) => {
                const pane = el('div', field.translatable ? { class: 'lang-pane', 'data-lang': lang } : {});
                const label = el('label', {}, field.label + (field.required && lang === 'ar' ? ' *' : ''));
                if (field.translatable) {
                    label.append(el('span', { class: 'lang-tag' }, langNames[lang]));
                    if (lang !== 'ar' && !String(values[lang] || '').trim() && String(values.ar || '').trim()) label.append(el('span', { class: 'missing' }, 'غير مترجم'));
                }
                pane.append(label);
                if (field.translatable && lang !== 'ar' && String(values.ar || '').trim()) pane.append(el('div', { class: 'ref', dir: 'rtl' }, String(values.ar).slice(0, 400)));
                const dir = lang === 'ar' ? 'rtl' : 'ltr';
                let input;
                const value = values[lang] ?? '';
                if (type === 'textarea' || type === 'html' || type === 'lines' || type === 'blocks') {
                    input = el('textarea', { name: nameFor(lang), dir, style: type === 'blocks' ? 'min-height:220px' : null });
                    input.value = value;
                } else if (type === 'select') {
                    input = el('select', { name: nameFor(lang) });
                    Object.entries(field.options || {}).forEach(([optionValue, optionLabel]) => {
                        const option = el('option', { value: optionValue }, optionLabel);
                        option.selected = String(value) === String(optionValue);
                        input.append(option);
                    });
                } else if (type === 'color') {
                    input = el('input', { type: 'color', name: nameFor(lang) });
                    input.value = /^#[0-9a-fA-F]{6}$/.test(value) ? value : '#1D3C78';
                } else if (type === 'password') {
                    input = el('input', { type: 'password', name: nameFor(lang), autocomplete: 'new-password', placeholder: 'اكتب لتغييرها' });
                } else {
                    input = el('input', { type: type === 'number' ? 'number' : 'text', name: nameFor(lang), dir: type === 'image' ? 'ltr' : dir });
                    input.value = value;
                    if (type === 'image') input.placeholder = 'رابط الصورة أو ارفع ملفاً';
                }
                pane.append(input);
                if (type === 'image') {
                    pane.append(el('input', { type: 'file', name: fileName, accept: 'image/jpeg,image/png,image/webp,image/gif', style: 'margin-top:8px' }));
                    if (value) pane.append(el('img', { class: 'preview', alt: '', src: mediaUrl(value) }));
                }
                wrap.append(pane);
            });
            if (field.hint) wrap.append(el('div', { class: 'hint' }, field.hint));
            return wrap;
        }

        function renderSection(data) {
            const box = document.getElementById('section-editor');
            box.innerHTML = '';
            box.append(el('h2', {}, data.label));
            if (data.settings.length) {
                const form = el('form');
                data.settings.forEach((s) => {
                    const values = { ar: s.value, fr: s.value_fr, en: s.value_en };
                    form.append(fieldControl(s, (lang) => (lang === 'ar' ? 'values' : 'values_' + lang) + '[' + s.key + ']', values, 'file_' + s.key));
                });
                const actions = el('div', { class: 'actions' });
                actions.append(el('button', { class: 'btn', type: 'submit' }, 'حفظ نصوص القسم'));
                form.append(actions);
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const fd = new FormData(form);
                    fd.append('action', 'save_settings');
                    fd.append('section', current);
                    await submitAndRefresh(fd);
                });
                box.append(form);
            }
            data.blocks.forEach((block) => box.append(renderBlock(block)));
        }

        function renderBlock(block) {
            const wrap = el('section');
            wrap.append(el('h3', {}, block.title), el('p', { class: 'intro' }, block.intro || ''));
            const add = el('button', { type: 'button', class: 'btn' }, 'إضافة');
            add.addEventListener('click', () => add.after(itemForm(block, blankValues(block), 0)));
            wrap.append(add);
            block.items.forEach((item) => {
                const row = el('div', { class: 'item' + (item.active ? '' : ' off') });
                row.append(el('span', {}, item.summary + (item.active ? '' : ' (مخفي)')));
                const actions = el('div', { class: 'actions' });
                actions.style.marginTop = '0';
                if (block.kind === 'packages') {
                    actions.append(smallButton('نسخ', () => itemAction(block, item.id, 'duplicate_item')));
                    if (item.url) {
                        const page = el('a', { href: item.url, target: '_blank', rel: 'noopener', class: 'btn tiny', style: 'text-decoration:none' }, 'الصفحة');
                        actions.append(page);
                    }
                }
                actions.append(
                    smallButton('تعديل', () => row.after(itemForm(block, item.values, item.id))),
                    smallButton('أعلى', () => itemAction(block, item.id, 'move_item', { direction: 'up' })),
                    smallButton('أسفل', () => itemAction(block, item.id, 'move_item', { direction: 'down' })),
                    smallButton('حذف', () => { if (confirm('حذف هذا العنصر من الموقع؟')) itemAction(block, item.id, 'delete_item'); }, true)
                );
                row.append(actions);
                wrap.append(row);
            });
            return wrap;
        }
        function smallButton(text, onClick, danger) {
            const b = el('button', { type: 'button', class: danger ? 'btn tiny danger' : 'btn tiny' }, text);
            b.addEventListener('click', onClick);
            return b;
        }
        function blankValues(block) {
            const values = {};
            Object.entries(block.fields).forEach(([name, field]) => {
                if (field.type === 'checkbox') values[name] = 1;
                else if (field.type === 'departures' || field.type === 'hotels') values[name] = [];
                else if (name === 'stars') values[name] = 5;
                else if (field.type === 'select') values[name] = Object.keys(field.options || {})[0] || '';
                else values[name] = '';
            });
            return values;
        }
        function itemForm(block, values, id) {
            document.querySelectorAll('.itemform').forEach((f) => f.remove());
            const form = el('form', { class: 'itemform' });
            Object.entries(block.fields).forEach(([name, field]) => {
                const langValues = { ar: values[name], fr: values[name + '_fr'], en: values[name + '_en'] };
                form.append(fieldControl(field, (lang) => (lang === 'ar' ? name : name + '_' + lang), langValues, 'file_' + name));
            });
            const actions = el('div', { class: 'actions' });
            actions.append(el('button', { class: 'btn', type: 'submit' }, id ? 'حفظ التعديل' : 'إضافة العنصر'), smallButton('إلغاء', () => form.remove()));
            form.append(actions);
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const fd = new FormData(form);
                fd.append('action', 'save_item');
                fd.append('kind', block.kind);
                fd.append('type', block.type || '');
                fd.append('id', String(id || 0));
                await submitAndRefresh(fd);
            });
            setTimeout(() => form.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 30);
            return form;
        }
        async function itemAction(block, id, action, extra) {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('kind', block.kind);
            fd.append('type', block.type || '');
            fd.append('id', String(id));
            Object.entries(extra || {}).forEach(([k, v]) => fd.append(k, v));
            await submitAndRefresh(fd);
        }
        async function submitAndRefresh(fd) {
            try {
                const data = await post(fd);
                setStatus(data.message);
                reloadPreview();
                await loadSection(current, false);
            } catch (error) {
                setStatus(error.message, true);
            }
        }

        async function loadSection(id, announce) {
            current = id;
            document.getElementById('section-jump').value = id;
            showTab('content');
            if (announce !== false) setStatus('جارٍ فتح القسم...');
            try {
                const response = await fetch('designer_api.php?section=' + encodeURIComponent(id));
                const data = await response.json();
                if (!data.ok) throw new Error(data.message || 'تعذر فتح القسم');
                renderSection(data);
                if (announce !== false) setStatus('عدّل ثم احفظ. التعديل يظهر في المعاينة بعد الحفظ.');
                tellFrame(announce !== false);
            } catch (error) {
                setStatus(error.message, true);
            }
        }
        function showTab(tab) {
            document.querySelectorAll('.tabs button').forEach((b) => b.classList.toggle('on', b.dataset.tab === tab));
            document.getElementById('pane-design').hidden = tab !== 'design';
            document.getElementById('pane-content').hidden = tab !== 'content';
        }

        document.querySelectorAll('.tabs button').forEach((b) => b.addEventListener('click', () => showTab(b.dataset.tab)));
        document.getElementById('section-jump').addEventListener('change', (event) => loadSection(event.target.value));
        document.getElementById('design-form').addEventListener('input', scheduleDesign);
        document.getElementById('design-form').addEventListener('change', scheduleDesign);
        document.getElementById('section-padding').addEventListener('input', (e) => { document.getElementById('pad-label').textContent = e.target.value; });
        document.getElementById('hero-min').addEventListener('input', (e) => { document.getElementById('hero-label').textContent = e.target.value; });
        document.getElementById('publish').addEventListener('click', () => simpleAction('publish_design'));
        document.getElementById('discard').addEventListener('click', () => simpleAction('discard_design', 'إلغاء كل تعديلات التصميم غير المنشورة؟'));
        document.getElementById('reset-design').addEventListener('click', () => simpleAction('reset_design', 'وضع الألوان والخطوط والترتيب الأصلية في المسودة؟ لن يراها الزوار حتى تنشرها.'));
        document.getElementById('make-snapshot').addEventListener('click', () => simpleAction('snapshot'));
        document.querySelectorAll('.device').forEach((b) => b.addEventListener('click', () => {
            document.querySelectorAll('.device').forEach((x) => x.classList.remove('on'));
            b.classList.add('on');
            frame.style.width = b.dataset.width;
        }));
        document.getElementById('preview-lang').addEventListener('change', (e) => { setEditLang(e.target.value); reloadPreview(); });
        document.querySelectorAll('#edit-lang button').forEach((b) => b.addEventListener('click', () => { setEditLang(b.dataset.lang); reloadPreview(); }));
        document.getElementById('pick-toggle').addEventListener('click', (event) => {
            picking = !picking;
            event.currentTarget.classList.toggle('on', picking);
            event.currentTarget.textContent = picking ? 'وضع التحديد' : 'وضع التفاعل';
            tellFrame(false);
            setStatus(picking ? 'اضغط قسماً في المعاينة لتحريره.' : 'يمكنك الآن تجربة القوائم والنماذج داخل المعاينة.');
        });
        window.addEventListener('message', (event) => {
            if (event.origin !== location.origin || !event.data) return;
            if (event.data.type === 'gf-open' && picking) loadSection(event.data.section);
        });
        frame.addEventListener('load', () => {
            applyLive();
            tellFrame(false);
            const y = parseInt(frame.dataset.restoreY || '0', 10);
            if (y && doc() && doc().defaultView) doc().defaultView.scrollTo(0, y);
        });
        window.addEventListener('beforeunload', (event) => {
            if (designAgain || designSaving) { event.preventDefault(); event.returnValue = ''; }
        });

        setEditLang(previewLang);
        renderOrder();
        frame.src = previewUrl();
    </script>
</body>
</html>
