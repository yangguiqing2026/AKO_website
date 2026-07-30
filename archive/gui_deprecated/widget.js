/**
 * AKO Website - 在线咨询 Widget (v3)
 * 自注入式浮动咨询窗口。
 *
 * v3 变更（2026-07-18，AGE-TECH-AKO-DB-003）：
 *  - 规格/参数/报价/FAQ 直连同域 /wall_api.php（墙板库，7×24，无需网关无需隧道）
 *  - 留资直连同域 /lead_api.php（入 MySQL leads 表）
 *  - RAG 长文问答才走网关 /api/chat；网关不可达 → 留资兜底，不再裸报网络异常
 *  - 网关地址：默认同源；方案 C 上云后 <script src="js/widget.js" data-api-base="https://网关域名">
 *
 * 部署：上传覆盖 /js/widget.js（index.html 无需改动）；同目录需有 wall_api.php 与 lead_api.php
 */
(function () {
  'use strict';

  var scriptEl = document.currentScript;
  // 网关（仅 RAG 用）：默认同源；未部署网关时走兜底话术，不报网络异常
  var API_BASE = (scriptEl && scriptEl.getAttribute('data-api-base')) || '';
  // 墙板库接口：同源相对路径（akobuild.cloud 同机），永无跨域/混合内容问题
  var WALL_API = '/wall_api.php';
  var LEAD_API = '/lead_api.php';

  // ====== 注入样式 ======
  var style = document.createElement('style');
  style.textContent = [
    '.ako-widget__btn{position:fixed;bottom:24px;right:24px;width:56px;height:56px;border-radius:50%;',
    'background:#231E1C;color:#EBDAB9;border:2px solid #B99B5F;font-size:24px;cursor:pointer;',
    'box-shadow:0 4px 16px rgba(0,0,0,.18);z-index:9999;display:flex;align-items:center;',
    'justify-content:center;transition:transform .2s;line-height:1;}',
    '.ako-widget__btn:hover{transform:scale(1.08);}',
    '.ako-widget__container{position:fixed;bottom:90px;right:24px;width:380px;height:560px;',
    'z-index:9998;display:none;flex-direction:column;}',
    '.ako-widget__container.open{display:flex;}',
    '.ako-widget__window{background:#EBDAB9;border:2px solid #231E1C;border-radius:12px;',
    'display:flex;flex-direction:column;height:100%;overflow:hidden;}',
    '.ako-widget__header{background:linear-gradient(135deg,#231E1C,#3a3330);color:#B99B5F;',
    'padding:14px 18px;font-weight:700;font-size:16px;border-radius:10px 10px 0 0;',
    'display:flex;align-items:center;gap:10px;flex-shrink:0;}',
    '.ako-widget__logo{font-size:22px;font-weight:900;letter-spacing:2px;color:#B99B5F;}',
    '.ako-widget__header-close{margin-left:auto;background:none;border:none;color:#EBDAB9;',
    'font-size:20px;cursor:pointer;padding:0;line-height:1;}',
    '.ako-widget__quick{display:flex;flex-wrap:wrap;gap:6px;padding:8px 16px 0;flex-shrink:0;}',
    '.ako-widget__quick-btn{background:#EBDAB9;border:1px solid #231E1C;color:#231E1C;',
    'padding:6px 12px;border-radius:16px;font-size:12px;cursor:pointer;transition:.2s;white-space:nowrap;}',
    '.ako-widget__quick-btn:hover{background:#C3BEB4;}',
    '.ako-widget__messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;}',
    '.ako-widget__msg-bot{align-self:flex-start;max-width:85%;background:#C3BEB4;color:#231E1C;',
    'padding:10px 14px;border-radius:4px 16px 16px 16px;border:1px solid #231E1C;font-size:14px;',
    'line-height:1.6;white-space:pre-wrap;word-break:break-word;}',
    '.ako-widget__msg-bot p{margin:0 0 4px;}',
    '.ako-widget__msg-bot p:last-child{margin-bottom:0;}',
    '.ako-widget__msg-user{align-self:flex-end;max-width:85%;background:#d9c8a8;color:#231E1C;',
    'padding:10px 14px;border-radius:16px 4px 16px 16px;border:1px solid #231E1C;font-size:14px;',
    'line-height:1.6;word-break:break-word;}',
    '.ako-widget__sources{margin-top:6px;font-size:12px;color:#B99B5F;cursor:pointer;user-select:none;font-weight:600;}',
    '.ako-widget__sources-list{display:none;margin-top:4px;font-size:12px;color:#231E1C;opacity:.8;}',
    '.ako-widget__sources-list.show{display:block;}',
    '.ako-widget__typing{display:flex;gap:4px;padding:8px 0;}',
    '.ako-widget__typing span{width:8px;height:8px;border-radius:50%;background:#A08C64;',
    'animation:akoWidgetBreathe 1.4s infinite ease-in-out both;}',
    '.ako-widget__typing span:nth-child(1){animation-delay:0s;}',
    '.ako-widget__typing span:nth-child(2){animation-delay:.2s;}',
    '.ako-widget__typing span:nth-child(3){animation-delay:.4s;}',
    '@keyframes akoWidgetBreathe{0%,80%,100%{transform:scale(.6);opacity:.4;}40%{transform:scale(1);opacity:1;}}',
    '.ako-widget__input-area{padding:12px 16px;display:flex;gap:8px;border-top:1px solid #C3BEB4;flex-shrink:0;}',
    '.ako-widget__input{flex:1;padding:10px 14px;border:1px solid #231E1C;border-radius:20px;',
    'background:#faf6ef;color:#231E1C;font-size:14px;outline:none;}',
    '.ako-widget__input:focus{border-color:#A08C64;}',
    '.ako-widget__send{padding:10px 20px;background:#231E1C;color:#EBDAB9;border:none;',
    'border-radius:20px;font-size:14px;font-weight:600;cursor:pointer;transition:.2s;}',
    '.ako-widget__send:hover{background:#4a3c38;}',
    '.ako-widget__send:disabled{opacity:.5;cursor:not-allowed;}',
    '.ako-widget__lead-form{background:#faf6ef;border:1px solid #231E1C;border-radius:12px;',
    'padding:16px;margin-top:8px;display:flex;flex-direction:column;gap:10px;}',
    '.ako-widget__lead-form input,.ako-widget__lead-form select{padding:10px 12px;',
    'border:1px solid #231E1C;border-radius:8px;background:#EBDAB9;color:#231E1C;font-size:14px;}',
    '.ako-widget__lead-submit{padding:10px;background:#A08C64;color:#fff;border:none;',
    'border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:.2s;}',
    '.ako-widget__lead-submit:hover{background:#8b7852;}',
    '.ako-widget__success{display:flex;align-items:center;gap:8px;color:#A08C64;',
    'font-weight:600;font-size:14px;padding:12px;}',
    '.ako-widget__lead-link{color:#B99B5F;cursor:pointer;font-weight:700;text-decoration:underline;}',
    '.ako-widget__footer{padding:6px 16px 10px;font-size:11px;color:#A08C64;text-align:center;flex-shrink:0;}',
    '@media(max-width:480px){.ako-widget__container{top:0;left:0;right:0;bottom:0;',
    'width:100%;height:100%;bottom:0;}.ako-widget__window{border-radius:0;border:none;}',
    '.ako-widget__header{border-radius:0;}.ako-widget__btn{bottom:16px;right:16px;}}'
  ].join('\n');
  document.head.appendChild(style);

  // ====== 注入 HTML ======
  var html = [
    '<div class="ako-widget__btn" id="akoWidgetBtn" title="在线咨询">💬</div>',
    '<div class="ako-widget__container" id="akoWidgetContainer">',
    ' <div class="ako-widget__window">',
    '  <div class="ako-widget__header">',
    '   <span class="ako-widget__logo">AKO ▼</span>',
    '   <span style="color:#EBDAB9;">阿格建筑 · 在线咨询</span>',
    '   <button class="ako-widget__header-close" id="akoWidgetClose">✕</button>',
    '  </div>',
    '  <div class="ako-widget__quick" id="akoWidgetQuick">',
    '   <button class="ako-widget__quick-btn" data-q="陶粒墙板有哪些厚度规格">产品规格</button>',
    '   <button class="ako-widget__quick-btn" data-q="墙板怎么报价">产品价格</button>',
    '   <button class="ako-widget__quick-btn" data-q="120mm墙板隔声量多少">隔声性能</button>',
    '  </div>',
    '  <div class="ako-widget__messages" id="akoWidgetMsgs">',
    '   <div class="ako-widget__msg-bot">您好，我是阿格建筑咨询助手 👋<br>可为您解答陶粒墙板规格、参数、报价与应用的疑问，请直接输入问题或点击上方快捷按钮。</div>',
    '  </div>',
    '  <div class="ako-widget__input-area">',
    '   <input class="ako-widget__input" id="akoWidgetInput" type="text" placeholder="输入您的问题..." />',
    '   <button class="ako-widget__send" id="akoWidgetSend">发送</button>',
    '  </div>',
    '  <div class="ako-widget__footer" id="akoWidgetFooter">数据由阿格墙板数据库提供支持</div>',
    ' </div>',
    '</div>'
  ].join('\n');

  var wrapper = document.createElement('div');
  wrapper.innerHTML = html;
  document.body.appendChild(wrapper);

  // ====== 逻辑 ======
  var sessionId = null;
  var isLoading = false;

  var $ = function (id) { return document.getElementById(id); };

  $('akoWidgetBtn').onclick = function () {
    $('akoWidgetContainer').classList.add('open');
    $('akoWidgetBtn').style.display = 'none';
    $('akoWidgetInput').focus();
  };
  $('akoWidgetClose').onclick = function () {
    $('akoWidgetContainer').classList.remove('open');
    $('akoWidgetBtn').style.display = 'flex';
  };

  var quickBtns = $('akoWidgetQuick').querySelectorAll('.ako-widget__quick-btn');
  quickBtns.forEach(function (btn) {
    btn.onclick = function () {
      $('akoWidgetInput').value = btn.getAttribute('data-q');
      sendMsg();
    };
  });

  $('akoWidgetInput').onkeydown = function (e) {
    if (e.key === 'Enter') sendMsg();
  };
  $('akoWidgetSend').onclick = sendMsg;

  // 页脚：墙板库更新日期（不依赖网关）
  (function () {
    wallGet('meta').then(function (rows) {
      if (!rows) return;
      var ver = '', date = '';
      rows.forEach(function (r) {
        if (r.meta_key === 'version') ver = r.meta_value;
        if (r.meta_key === 'updated_at') date = r.meta_value;
      });
      if (date) $('akoWidgetFooter').textContent = '墙板数据 ' + ver + ' · 更新至 ' + date + ' · 阿格墙板数据库';
    });
  })();

  // ====== 墙板库取数（同源，带会话缓存，失败返回 null） ======
  var _wallCache = {};
  function wallGet(type, thickness) {
    var key = type + ':' + (thickness || 0);
    if (_wallCache[key]) return Promise.resolve(_wallCache[key]);
    var url = WALL_API + '?type=' + encodeURIComponent(type);
    if (thickness) url += '&thickness=' + thickness;
    return fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j || !j.ok) return null;
        _wallCache[key] = j.data;
        return j.data;
      })
      .catch(function () { return null; });
  }

  // ====== 结构化问题路由（与网关 R010/R020/R030 口径一致） ======
  var STRUCT_RULES = [
    { re: /厚度|规格|型号|多厚|尺寸|板幅/, type: 'panels' },
    { re: /隔声|隔音|耐火|防火|抗风|水密|气密|放射|环保|吊挂|软化|防潮|防霉|保温|传热|检测/, type: 'specs' },
    { re: /价格|报价|多少钱|造价|单价|一平/, type: 'pricing' }
  ];
  var SRC_DB = [{ display_name: '《阿格墙板数据库》', score: 1 }];

  function extractThickness(q) {
    var m = q.match(/100|120|140|150|180|200/);
    return m ? parseInt(m[0], 10) : 0;
  }

  function buildPanels(rows) {
    var thks = [], types = {};
    rows.forEach(function (r) {
      if (thks.indexOf(r.thickness_mm) === -1) thks.push(r.thickness_mm);
      types[r.panel_type] = true;
    });
    thks.sort(function (a, b) { return a - b; });
    var t = '我们的陶粒发泡混凝土墙板有 **' + thks.join(' / ') + 'mm** 六档厚度，分**承重墙板**与**非承重挂板**两类；' +
      '最大板幅 4000×10000mm（单板 40㎡，3mm 极窄缝），A 级不燃。需要哪个厚度的参数，直接问我。';
    return { html: simpleMd(t), sources: SRC_DB };
  }

  function buildSpecs(rows, thk) {
    if (!rows || !rows.length) {
      return { html: simpleMd('该厚度的实测参数**检测中，陆续上库**。可先问我 120mm 的检测数据，或留下联系方式获取最新进展。'), sources: SRC_DB, lead: true };
    }
    var lines = [];
    var hasMeasured = false;
    rows.slice(0, 8).forEach(function (r) {
      var tail = '';
      if (r.data_status === '实测') { hasMeasured = true; tail = r.report_no ? '（报告 ' + r.report_no + '）' : ''; }
      else if (r.data_status === '呈报') { tail = '（技术说明口径）'; }
      lines.push('- **' + r.spec_item + '**：' + r.spec_value + (r.unit ? ' ' + r.unit : '') + tail);
    });
    var head = thk ? thk + 'mm 墙板检测数据：' : '墙板检测数据：';
    var foot = (thk && !hasMeasured) ? '\n\n' + thk + 'mm 实测参数**检测中，陆续上库**；以上为通用口径数据。' : '';
    return { html: simpleMd(head + '\n' + lines.join('\n') + foot), sources: SRC_DB, lead: (thk && !hasMeasured) };
  }

  function buildPricing(rows) {
    var cz = null, gb = null;
    rows.forEach(function (r) {
      var p = parseFloat(r.price_from);
      if (r.panel_type === '承重墙板' && (cz === null || p < cz)) cz = p;
      if (r.panel_type === '非承重挂板' && (gb === null || p < gb)) gb = p;
    });
    var t = '墙板起售价：**承重墙板 ' + (cz || 450) + ' 元/㎡ 起**，**非承重挂板 ' + (gb || 400) + ' 元/㎡ 起**。\n' +
      '最终价格按厚度、构造、订单量与项目地确定，以正式报价单为准。';
    return { html: simpleMd(t) + '<div style="margin-top:6px;">👉 <span class="ako-widget__lead-link" id="akoPriceLead">获取正式报价</span></div>', sources: SRC_DB, priceLead: true };
  }

  // FAQ 二元组重叠匹配（≥2 个二连字重合即命中）
  function bigrams(s) {
    var set = {}, out = [];
    s = s.replace(/[？?，。、\s]/g, '');
    for (var i = 0; i < s.length - 1; i++) { var g = s.substr(i, 2); if (!set[g]) { set[g] = 1; out.push(g); } }
    return out;
  }
  function matchFaq(faqs, q) {
    var qb = bigrams(q), best = null, bestScore = 1;
    faqs.forEach(function (f) {
      var fb = bigrams(f.question), score = 0;
      fb.forEach(function (g) { if (qb.indexOf(g) !== -1) score++; });
      if (score > bestScore) { bestScore = score; best = f; }
    });
    return best;
  }

  function structAnswer(q) {
    for (var i = 0; i < STRUCT_RULES.length; i++) {
      if (STRUCT_RULES[i].re.test(q)) {
        var t = STRUCT_RULES[i].type;
        if (t === 'panels') return wallGet('panels').then(function (r) { return r ? buildPanels(r) : null; });
        if (t === 'pricing') return wallGet('pricing').then(function (r) { return r ? buildPricing(r) : null; });
        var thk = extractThickness(q);
        return wallGet('specs', thk).then(function (r) { return r ? buildSpecs(r, thk) : null; });
      }
    }
    return wallGet('faq').then(function (faqs) {
      if (!faqs) return null;
      var f = matchFaq(faqs, q);
      return f ? { html: simpleMd(f.answer), sources: SRC_DB } : null;
    });
  }

  // ====== UI helpers ======
  function addMsg(role, html, sources) {
    var msgs = $('akoWidgetMsgs');
    var div = document.createElement('div');
    div.className = role === 'user' ? 'ako-widget__msg-user' : 'ako-widget__msg-bot';
    div.innerHTML = html;

    if (sources && sources.length) {
      var srcWrap = document.createElement('div');
      srcWrap.className = 'ako-widget__sources';
      srcWrap.textContent = '参考来源 ▸';
      var srcList = document.createElement('div');
      srcList.className = 'ako-widget__sources-list';
      srcList.innerHTML = sources.map(function (s, i) {
        return '<div>[' + (i + 1) + '] ' + (s.display_name || s) + '</div>';
      }).join('');
      srcWrap.onclick = function () { srcList.classList.toggle('show'); };
      div.appendChild(srcWrap);
      div.appendChild(srcList);
    }

    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
    return div;
  }

  function addTyping() {
    var msgs = $('akoWidgetMsgs');
    var div = document.createElement('div');
    div.className = 'ako-widget__msg-bot';
    div.id = 'akoTyping';
    div.innerHTML = '<div class="ako-widget__typing"><span></span><span></span><span></span></div>';
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
  }

  function rmTyping() {
    var el = document.getElementById('akoTyping');
    if (el) el.remove();
  }

  function addLeadForm() {
    if (document.getElementById('akoLeadForm')) return;
    var msgs = $('akoWidgetMsgs');
    var div = document.createElement('div');
    div.className = 'ako-widget__msg-bot';
    div.innerHTML = [
      '<div style="margin-bottom:10px;">这个问题我为您转接专属顾问，请留下联系方式，稍后第一时间回复您。</div>',
      '<div class="ako-widget__lead-form" id="akoLeadForm">',
      ' <input type="text" id="akoLeadName" placeholder="您的姓名" />',
      ' <input type="text" id="akoLeadPhone" placeholder="手机号码" />',
      ' <select id="akoLeadMarket">',
      '  <option value="">请选择意向市场</option>',
      '  <option value="城市更新">城市更新</option>',
      '  <option value="文旅民宿">文旅民宿</option>',
      '  <option value="乡村民居">乡村民居</option>',
      ' </select>',
      ' <button class="ako-widget__lead-submit" id="akoLeadSubmitBtn">提交咨询</button>',
      '</div>'
    ].join('\n');
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
    document.getElementById('akoLeadSubmitBtn').onclick = submitLead;
  }

  function submitLead() {
    var name = document.getElementById('akoLeadName').value.trim();
    var phone = document.getElementById('akoLeadPhone').value.trim();
    var market = document.getElementById('akoLeadMarket').value;

    if (!name || !phone || !market) {
      alert('请填写完整信息');
      return;
    }

    fetch(LEAD_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: name, phone: phone, market: market, message: '' })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.ok) {
          var form = document.getElementById('akoLeadForm');
          form.innerHTML = '<div class="ako-widget__success"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>提交成功，专属顾问将尽快联系您</div>';
        } else {
          alert('提交失败，请稍后重试');
        }
      })
      .catch(function () { alert('提交失败，请稍后重试'); });
  }

  // ====== 轻量 Markdown → HTML ======
  function simpleMd(text) {
    var html = text
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/^### (.+)$/gm, '<h4>$1</h4>')
      .replace(/^## (.+)$/gm, '<h3>$1</h3>')
      .replace(/^# (.+)$/gm, '<h2>$1</h2>')
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.+?)\*/g, '<em>$1</em>')
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/^- (.+)$/gm, '<li>$1</li>')
      .replace(/(<li>[\s\S]*?<\/li>)/g, '<ul>$1</ul>')
      .replace(/\n\n/g, '</p><p>')
      .replace(/\n/g, '<br>');
    return '<p>' + html + '</p>';
  }

  // ====== 网关 RAG（仅长文问答；不可达 → 留资兜底） ======
  function chatViaGateway(question) {
    var botDiv = null;
    var fullText = '';
    var metaReceived = false;

    fetch(API_BASE + '/api/chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ question: question, session_id: sessionId })
    })
      .then(function (resp) {
        if (!resp.ok) throw new Error('gw');
        var ct = resp.headers.get('content-type') || '';

        if (ct.indexOf('application/json') !== -1 || ct.indexOf('text/event-stream') === -1) {
          return resp.json().then(function (data) {
            rmTyping();
            sessionId = data.session_id;
            addMsg('bot', data.answer);
            if (data.action === 'lead') addLeadForm();
            isLoading = false;
            $('akoWidgetSend').disabled = false;
          });
        }

        var reader = resp.body.getReader();
        var decoder = new TextDecoder();
        var buffer = '';

        function pump() {
          return reader.read().then(function (result) {
            if (result.done) {
              isLoading = false;
              $('akoWidgetSend').disabled = false;
              $('akoWidgetInput').focus();
              return;
            }
            buffer += decoder.decode(result.value, { stream: true });
            var lines = buffer.split('\n');
            buffer = lines.pop() || '';
            for (var i = 0; i < lines.length; i++) {
              var line = lines[i];
              if (line.indexOf('data: ') !== 0) continue;
              var dataStr = line.slice(6).trim();
              try {
                var data = JSON.parse(dataStr);
                if (!metaReceived) {
                  metaReceived = true;
                  sessionId = data.session_id;
                  rmTyping();
                  botDiv = addMsg('bot', '', data.sources);
                  continue;
                }
                if (data.content) {
                  fullText += data.content;
                  if (botDiv) {
                    botDiv.innerHTML = simpleMd(fullText);
                    $('akoWidgetMsgs').scrollTop = $('akoWidgetMsgs').scrollHeight;
                  }
                }
              } catch (e) {}
            }
            return pump();
          });
        }
        return pump();
      })
      .catch(function () {
        rmTyping();
        addMsg('bot', '智能问答暂未开放。产品规格、参数、报价类问题可直接问我（如「墙板厚度」「120的隔声」「怎么报价」）；其他问题请留下联系方式，顾问稍后为您解答。');
        addLeadForm();
        isLoading = false;
        $('akoWidgetSend').disabled = false;
      });
  }

  // ====== 核心：发送消息（先墙板库直答，未命中再走网关） ======
  function sendMsg() {
    var input = $('akoWidgetInput');
    var question = input.value.trim();
    if (!question || isLoading) return;
    isLoading = true;
    input.value = '';
    $('akoWidgetSend').disabled = true;

    addMsg('user', question);
    $('akoWidgetQuick').style.display = 'none';
    addTyping();

    structAnswer(question)
      .then(function (ans) {
        if (ans) {
          rmTyping();
          addMsg('bot', ans.html, ans.sources);
          if (ans.lead) addLeadForm();
          if (ans.priceLead) {
            var link = document.getElementById('akoPriceLead');
            if (link) link.onclick = addLeadForm;
          }
          isLoading = false;
          $('akoWidgetSend').disabled = false;
          $('akoWidgetInput').focus();
        } else {
          chatViaGateway(question);
        }
      })
      .catch(function () { chatViaGateway(question); });
  }
})();
