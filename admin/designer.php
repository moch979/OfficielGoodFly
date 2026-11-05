<?php

require __DIR__ . '/_init.php';

$state = designer_state();
$csrf = csrf_token();
$preview = '../index.php?designer=1';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>المصمم المرئي | Good Fly</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; overflow: hidden; font-family: Tajawal, Tahoma, sans-serif; background: #102924; color: #183B35; }
        button, input, select, textarea { font-family: inherit; }
        .top { height: 58px; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 14px; color: #F7F5EF; }
        .top b { color: #D8C39B; letter-spacing: .4px; }
        .top a { color: #EADBC0; text-decoration: none; font-size: 13px; }
        .cluster { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .top button, .ghost {
            background: transparent; color: #F7F5EF; border: 1px solid rgba(216,195,155,.45);
            border-radius: 999px; padding: 6px 10px; cursor: pointer; font-size: 13px;
        }
        .top button.on { background: #D8C39B; color: #102924; border-color: #D8C39B; font-weight: 800; }
        .shell { height: calc(100% - 58px); display: flex; min-height: 0; }
        .panel { width: 400px; background: #F7F5EF; overflow: auto; border-left: 1px solid rgba(216,195,155,.4); }
        .stage { flex: 1; background: #d9d4c8; display: flex; justify-content: center; min-width: 0; }
        iframe { width: 100%; height: 100%; border: 0; background: #fff; box-shadow: 0 0 40px rgba(16,41,36,.18); }
        .tabs { display: flex; gap: 6px; padding: 12px; position: sticky; top: 0; background: #F7F5EF; z-index: 2; }
        .tabs button { flex: 1; border: 1px solid #eadfca; background: #fff; border-radius: 999px; padding: 8px; cursor: pointer; font-weight: 700; }
        .tabs button.on { background: #183B35; color: #F7F5EF; }
        .pane { padding: 0 14px 28px; }
        .status { min-height: 18px; font-size: 12px; color: #405650; padding: 0 14px 8px; }
        .status.err { color: #8d2f2f; }
        label { display: block; font-weight: 800; font-size: 13px; margin: 12px 0 6px; }
        input[type=text], input[type=number], input[type=url], textarea, select {
            width: 100%; padding: 10px 12px; border: 1px solid #e6e1d6; border-radius: 12px; background: #fff; color: #183B35;
        }
        textarea { min-height: 88px; resize: vertical; }
        .hint { color: #6d746f; font-size: 12px; margin-top: 4px; }
        .colors { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .swatch { background: #fff; border: 1px solid #eadfca; border-radius: 14px; padding: 8px; }
        .swatch span { display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; }
        .swatch input { width: 100%; height: 36px; border: 0; background: transparent; padding: 0; }
        .check { display: flex; align-items: center; gap: 8px; font-weight: 700; margin: 8px 0; }
        .check input { width: auto; }
        .btn { border: 0; border-radius: 999px; padding: 10px 14px; font-weight: 800; cursor: pointer; background: linear-gradient(135deg,#D8C39B,#C4A976); color: #102924; }
        .btn.tiny { padding: 6px 10px; font-size: 12px; background: #fff; border: 1px solid #eadfca; }
        .btn.danger { background: #8d2f2f; color: #fff; }
        .order { display: flex; flex-direction: column; gap: 6px; }
        .order div, .item { display: flex; justify-content: space-between; gap: 8px; align-items: center; background: #fff; border: 1px solid #eadfca; border-radius: 12px; padding: 8px 10px; flex-wrap: wrap; }
        .item span { flex: 1; min-width: 120px; }
        .item .actions { margin-top: 0; }
        .item span { font-size: 13px; }
        h2 { font-size: 22px; margin: 8px 0; }
        h3 { font-size: 16px; margin: 18px 0 6px; }
        .intro { color: #405650; font-size: 13px; margin: 0 0 8px; }
        .preview { width: 100%; height: 92px; object-fit: cover; border-radius: 10px; margin-top: 8px; }
        .actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 12px; }
        @media (max-width: 900px) {
            .shell { display: block; overflow: auto; height: auto; }
            .panel, .stage { width: auto; height: 70vh; }
            html, body { overflow: auto; }
        }
    </style>
</head>
<body>
    <header class="top">
        <div class="cluster">
            <b>GOOD FLY</b>
            <span>المصمم المرئي</span>
        </div>
        <div class="cluster">
            <button type="button" class="device on" data-width="100%">حاسوب</button>
            <button type="button" class="device" data-width="820px">لوحي</button>
            <button type="button" class="device" data-width="390px">جوال</button>
            <button type="button" id="pick-toggle" class="on">وضع التحديد</button>
        </div>
        <div class="cluster">
            <span id="status"></span>
            <a href="index.php">لوحة التحكم</a>
            <a href="../index.php" target="_blank">الموقع</a>
        </div>
    </header>
    <div class="shell">
        <aside class="panel">
            <div class="tabs">
                <button type="button" class="on" data-tab="design">التصميم</button>
                <button type="button" data-tab="content">المحتوى</button>
            </div>
            <div class="status" id="panel-status">اضغط أي قسم في المعاينة لتعديل نصوصه وإضافة عناصره.</div>
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
                    <h2>الخطوط والمقاس</h2>
                    <label>خط النصوص</label>
                    <select name="body_font" id="body-font">
                        <?php foreach ($state['fonts'] as $name => $label): ?>
                        <option value="<?= h($name) ?>"<?= $state['bodyFont'] === $name ? ' selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>خط العناوين</label>
                    <select name="title_font" id="title-font">
                        <?php foreach ($state['fonts'] as $name => $label): ?>
                        <option value="<?= h($name) ?>"<?= $state['titleFont'] === $name ? ' selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>فراغ الأقسام: <span id="pad-label"><?= (int) $state['sectionPadding'] ?></span>px</label>
                    <input type="range" name="section_padding" id="section-padding" min="48" max="160" value="<?= (int) $state['sectionPadding'] ?>">
                    <label>ارتفاع الواجهة: <span id="hero-label"><?= (int) $state['heroMin'] ?></span>%</label>
                    <input type="range" name="hero_min_height" id="hero-min" min="50" max="100" value="<?= (int) $state['heroMin'] ?>">
                    <h2>إظهار الأقسام</h2>
                    <?php foreach ($state['flags'] as $key => $flag): ?>
                    <label class="check"><input type="checkbox" name="flags[<?= h($key) ?>]" value="1" data-flag="<?= h($key) ?>"<?= $flag['on'] ? ' checked' : '' ?>> <?= h($flag['label']) ?></label>
                    <?php endforeach; ?>
                    <h2>ترتيب الأقسام</h2>
                    <p class="intro">الشريط والشعار والتذييل يبقيان في مكانهما. هذه الأقسام تتحرك بينها.</p>
                    <div class="order" id="order-list"></div>
                </form>
            </div>
            <div class="pane" id="pane-content" hidden>
                <label>القسم</label>
                <select id="section-jump">
                    <?php foreach ($state['sections'] as $id => $label): ?>
                    <option value="<?= h($id) ?>"><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="section-editor"><p class="intro">اختر قسماً من القائمة أو من المعاينة.</p></div>
            </div>
        </aside>
        <div class="stage">
            <iframe id="preview" title="معاينة الموقع" src="<?= h($preview) ?>"></iframe>
        </div>
    </div>
    <script>
        const csrf = <?= json_for_script($csrf) ?>;
        const stacks = <?= json_for_script($state['fontStacks']) ?>;
        const flagZones = <?= json_for_script($state['flagZones']) ?>;
        const orderLabels = <?= json_for_script($state['orderLabels']) ?>;
        const colorVars = {
            color_green: '--gf-green',
            color_darkgreen: '--gf-darkgreen',
            color_gold: '--gf-gold',
            color_lightgold: '--gf-lightgold',
            color_ivory: '--gf-ivory',
            color_lightivory: '--gf-lightivory',
            color_slate: '--gf-slate'
        };
        const frame = document.getElementById('preview');
        const status = document.getElementById('panel-status');
        let picking = true;
        let current = 'hero';
        let order = <?= json_for_script($state['order']) ?>;
        let designTimer = null;
        let designSaving = false;
        let designAgain = false;

        function setStatus(message, isError) {
            status.textContent = message || '';
            status.classList.toggle('err', !!isError);
        }

        function doc() {
            return frame.contentDocument;
        }

        function channels(hex) {
            const value = hex.replace('#', '');
            return parseInt(value.slice(0, 2), 16) + ' ' + parseInt(value.slice(2, 4), 16) + ' ' + parseInt(value.slice(4, 6), 16);
        }

        function applyLive() {
            const root = doc() && doc().documentElement;
            if (!root) return;
            document.querySelectorAll('[data-color]').forEach((input) => {
                root.style.setProperty(colorVars[input.dataset.color], channels(input.value));
            });
            root.style.setProperty('--gf-body', stacks[document.getElementById('body-font').value] || stacks.Tajawal);
            root.style.setProperty('--gf-title', stacks[document.getElementById('title-font').value] || stacks.Amiri);
            root.style.setProperty('--gf-section-y', document.getElementById('section-padding').value + 'px');
            root.style.setProperty('--gf-hero-min', document.getElementById('hero-min').value + 'vh');
            order.forEach((id, index) => {
                const el = doc().querySelector('#gf-flow > [data-gf="' + id + '"]');
                if (el) el.style.order = String(index + 1);
            });
            document.querySelectorAll('[data-flag]').forEach((input) => {
                const on = input.checked;
                const zone = flagZones[input.dataset.flag];
                if (zone) {
                    doc().querySelectorAll('[data-gf="' + zone + '"]').forEach((el) => el.classList.toggle('gf-off', !on));
                }
                doc().querySelectorAll('[data-gf-flag="' + input.dataset.flag + '"]').forEach((el) => el.classList.toggle('gf-off', !on));
            });
        }

        function tellFrame() {
            if (!frame.contentWindow) return;
            frame.contentWindow.postMessage({ type: 'gf-pick', on: picking }, location.origin);
            if (current) frame.contentWindow.postMessage({ type: 'gf-focus', section: current }, location.origin);
        }

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
                setStatus(data.message || 'تم حفظ التصميم');
            } catch (error) {
                setStatus(error.message, true);
            } finally {
                designSaving = false;
                if (designAgain) scheduleDesign();
            }
        }

        async function post(fd) {
            if (!fd.has('csrf')) fd.append('csrf', csrf);
            const response = await fetch('designer_api.php', { method: 'POST', body: fd });
            const data = await response.json();
            if (!data.ok) throw new Error(data.message || 'تعذر الحفظ');
            return data;
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
                const up = document.createElement('button');
                up.type = 'button';
                up.className = 'btn tiny';
                up.textContent = 'أعلى';
                up.disabled = index === 0;
                up.addEventListener('click', () => moveSection(index, -1));
                const down = document.createElement('button');
                down.type = 'button';
                down.className = 'btn tiny';
                down.textContent = 'أسفل';
                down.disabled = index === order.length - 1;
                down.addEventListener('click', () => moveSection(index, 1));
                actions.append(up, down);
                row.append(name, actions);
                box.append(row);
            });
            document.getElementById('section-order').value = order.join(',');
        }

        function moveSection(index, step) {
            const next = index + step;
            if (!order[next]) return;
            const copy = order.slice();
            const moved = copy[index];
            copy[index] = copy[next];
            copy[next] = moved;
            order = copy;
            renderOrder();
            scheduleDesign();
        }

        function fieldControl(name, field, value) {
            const wrap = document.createElement('div');
            const label = document.createElement('label');
            label.textContent = field.label || name;
            wrap.append(label);
            let input;
            if (field.type === 'textarea' || field.type === 'html') {
                input = document.createElement('textarea');
                input.value = value || '';
            } else if (field.type === 'select') {
                input = document.createElement('select');
                Object.entries(field.options || {}).forEach(([optionValue, optionLabel]) => {
                    const option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = optionLabel;
                    option.selected = String(value) === String(optionValue);
                    input.append(option);
                });
            } else if (field.type === 'checkbox' || field.type === 'bool') {
                const check = document.createElement('label');
                check.className = 'check';
                input = document.createElement('input');
                input.type = 'checkbox';
                input.value = '1';
                input.checked = String(value) === '1' || value === 1 || value === true;
                check.append(input, document.createTextNode(' ظاهر'));
                wrap.append(check);
            } else if (field.type === 'image') {
                input = document.createElement('input');
                input.type = 'text';
                input.value = value || '';
                input.placeholder = 'رابط الصورة أو ارفع ملفاً';
            } else if (field.type === 'color') {
                input = document.createElement('input');
                input.type = 'color';
                input.value = /^#[0-9a-fA-F]{6}$/.test(value || '') ? value : '#183B35';
            } else {
                input = document.createElement('input');
                input.type = field.type === 'number' ? 'number' : 'text';
                input.value = value ?? '';
            }
            input.name = field.type === 'checkbox' || field.type === 'bool' ? name : name;
            if (field.type !== 'checkbox' && field.type !== 'bool') wrap.append(input);
            if (field.type === 'image') {
                const file = document.createElement('input');
                file.type = 'file';
                file.accept = 'image/*';
                const matched = String(name).match(/^values\[(.+)\]$/);
                file.name = matched ? 'file_' + matched[1] : (name === 'image' ? 'file_image' : 'file_' + name);
                file.style.marginTop = '8px';
                wrap.append(file);
                if (value) {
                    const img = document.createElement('img');
                    img.className = 'preview';
                    img.alt = '';
                    img.src = mediaUrl(value);
                    wrap.append(img);
                }
            }
            if (field.hint) {
                const hint = document.createElement('div');
                hint.className = 'hint';
                hint.textContent = field.hint;
                wrap.append(hint);
            }
            return wrap;
        }

        function mediaUrl(value) {
            if (!value) return '';
            if (/^(https?:)?\/\//i.test(value) || value.startsWith('/') || value.startsWith('data:')) return value;
            return '../' + value.replace(/^\.\//, '');
        }

        function renderSection(data) {
            const box = document.getElementById('section-editor');
            box.innerHTML = '';
            const title = document.createElement('h2');
            title.textContent = data.label;
            box.append(title);
            if (data.settings.length) {
                const form = document.createElement('form');
                data.settings.forEach((field) => form.append(fieldControl('values[' + field.key + ']', field, field.value)));
                const save = document.createElement('button');
                save.className = 'btn';
                save.type = 'submit';
                save.textContent = 'حفظ نصوص القسم';
                const actions = document.createElement('div');
                actions.className = 'actions';
                actions.append(save);
                form.append(actions);
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const fd = new FormData(form);
                    fd.append('action', 'save_settings');
                    fd.append('section', current);
                    try {
                        const result = await post(fd);
                        setStatus(result.message);
                        reloadPreview();
                    } catch (error) {
                        setStatus(error.message, true);
                    }
                });
                box.append(form);
            }
            data.blocks.forEach((block) => box.append(renderBlock(block)));
        }

        function renderBlock(block) {
            const wrap = document.createElement('section');
            const title = document.createElement('h3');
            title.textContent = block.title;
            const intro = document.createElement('p');
            intro.className = 'intro';
            intro.textContent = block.intro || '';
            const add = document.createElement('button');
            add.type = 'button';
            add.className = 'btn';
            add.textContent = 'إضافة';
            add.addEventListener('click', () => wrap.append(itemForm(block, blankValues(block))));
            wrap.append(title, intro, add);
            block.items.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'item';
                const name = document.createElement('span');
                name.textContent = item.summary;
                const actions = document.createElement('div');
                actions.className = 'actions';
                actions.append(
                    smallButton('تعديل', () => wrap.append(itemForm(block, item.values, item.id))),
                    smallButton('أعلى', () => moveItem(block, item.id, 'up')),
                    smallButton('أسفل', () => moveItem(block, item.id, 'down')),
                    smallButton('حذف', () => removeItem(block, item.id), true)
                );
                row.append(name, actions);
                wrap.append(row);
            });
            return wrap;
        }

        function smallButton(text, onClick, danger) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = danger ? 'btn tiny danger' : 'btn tiny';
            button.textContent = text;
            button.addEventListener('click', onClick);
            return button;
        }

        function blankValues(block) {
            const values = {};
            let maxOrder = 0;
            block.items.forEach((item) => {
                maxOrder = Math.max(maxOrder, Number(item.values.sort_order) || 0);
            });
            Object.entries(block.fields).forEach(([name, field]) => {
                if (field.type === 'checkbox') values[name] = 1;
                else if (name === 'stars') values[name] = 5;
                else if (name === 'sort_order') values[name] = maxOrder + 10;
                else if (field.type === 'select') values[name] = Object.keys(field.options || {})[0] || '';
                else values[name] = '';
            });
            return values;
        }

        function itemForm(block, values, id) {
            const existing = document.getElementById('item-form');
            if (existing) existing.remove();
            const form = document.createElement('form');
            form.id = 'item-form';
            Object.entries(block.fields).forEach(([name, field]) => {
                form.append(fieldControl(name, field, values[name]));
            });
            const save = document.createElement('button');
            save.className = 'btn';
            save.type = 'submit';
            save.textContent = id ? 'حفظ التعديل' : 'إضافة العنصر';
            const cancel = smallButton('إلغاء', () => form.remove());
            const actions = document.createElement('div');
            actions.className = 'actions';
            actions.append(save, cancel);
            form.append(actions);
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const fd = new FormData(form);
                fd.append('action', 'save_item');
                fd.append('kind', block.kind);
                fd.append('type', block.type || '');
                fd.append('id', String(id || 0));
                try {
                    const result = await post(fd);
                    setStatus(result.message);
                    reloadPreview();
                    loadSection(current);
                } catch (error) {
                    setStatus(error.message, true);
                }
            });
            return form;
        }

        async function moveItem(block, id, direction) {
            const fd = new FormData();
            fd.append('action', 'move_item');
            fd.append('kind', block.kind);
            fd.append('type', block.type || '');
            fd.append('id', String(id));
            fd.append('direction', direction);
            try {
                const result = await post(fd);
                setStatus(result.message);
                reloadPreview();
                loadSection(current);
            } catch (error) {
                setStatus(error.message, true);
            }
        }

        async function removeItem(block, id) {
            if (!confirm('حذف هذا العنصر من الموقع؟')) return;
            const fd = new FormData();
            fd.append('action', 'delete_item');
            fd.append('kind', block.kind);
            fd.append('type', block.type || '');
            fd.append('id', String(id));
            try {
                const result = await post(fd);
                setStatus(result.message);
                reloadPreview();
                loadSection(current);
            } catch (error) {
                setStatus(error.message, true);
            }
        }

        async function loadSection(id) {
            current = id;
            document.getElementById('section-jump').value = id;
            document.querySelector('[data-tab="content"]').click();
            setStatus('جارٍ فتح القسم...');
            try {
                const response = await fetch('designer_api.php?section=' + encodeURIComponent(id));
                const data = await response.json();
                if (!data.ok) throw new Error(data.message || 'تعذر فتح القسم');
                renderSection(data);
                setStatus('عدّل القسم ثم احفظ. المعاينة تتحدث بعد الحفظ.');
                tellFrame();
            } catch (error) {
                setStatus(error.message, true);
            }
        }

        function reloadPreview() {
            frame.src = '<?= h($preview) ?>&v=' + Date.now();
        }

        document.querySelectorAll('.tabs button').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tabs button').forEach((item) => item.classList.remove('on'));
                button.classList.add('on');
                document.getElementById('pane-design').hidden = button.dataset.tab !== 'design';
                document.getElementById('pane-content').hidden = button.dataset.tab !== 'content';
            });
        });
        document.getElementById('section-jump').addEventListener('change', (event) => loadSection(event.target.value));
        document.getElementById('design-form').addEventListener('input', scheduleDesign);
        document.getElementById('design-form').addEventListener('change', scheduleDesign);
        document.getElementById('section-padding').addEventListener('input', (event) => {
            document.getElementById('pad-label').textContent = event.target.value;
        });
        document.getElementById('hero-min').addEventListener('input', (event) => {
            document.getElementById('hero-label').textContent = event.target.value;
        });
        document.querySelectorAll('.device').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.device').forEach((item) => item.classList.remove('on'));
                button.classList.add('on');
                frame.style.width = button.dataset.width;
            });
        });
        document.getElementById('pick-toggle').addEventListener('click', (event) => {
            picking = !picking;
            event.currentTarget.classList.toggle('on', picking);
            event.currentTarget.textContent = picking ? 'وضع التحديد' : 'وضع التفاعل';
            tellFrame();
            setStatus(picking ? 'اضغط قسماً في المعاينة لتحريره.' : 'يمكنك الآن تجربة القوائم والنماذج داخل المعاينة.');
        });
        window.addEventListener('message', (event) => {
            if (event.origin !== location.origin || !event.data || event.data.type !== 'gf-open') return;
            if (!picking) return;
            loadSection(event.data.section);
        });
        frame.addEventListener('load', () => {
            applyLive();
            tellFrame();
        });
        renderOrder();
    </script>
</body>
</html>
