/* ========================================
   AKO Website - Main JS
   极简交互：导航、表单验证、卡片展开
   ======================================== */

document.addEventListener('DOMContentLoaded', function () {

  // --- Navbar scroll effect ---
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 60) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  }

  // --- Hamburger menu ---
  const hamburger = document.querySelector('.hamburger');
  const mobileMenu = document.querySelector('.mobile-menu');

  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', function () {
      hamburger.classList.toggle('open');
      mobileMenu.classList.toggle('open');
      document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
    });

    // Close menu on link click
    mobileMenu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        hamburger.classList.remove('open');
        mobileMenu.classList.remove('open');
        document.body.style.overflow = '';
      });
    });
  }

  // --- Active nav link ---
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.navbar__links a').forEach(function (link) {
    const href = link.getAttribute('href');
    if (href === currentPage || (currentPage === '' && href === 'index.html')) {
      link.classList.add('active');
    }
  });

  // --- Product card expand/collapse ---
  document.querySelectorAll('.product-card__toggle').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      const card = this.closest('.card');
      const detail = card.querySelector('.product-card__detail');
      if (detail) {
        const isOpen = detail.classList.contains('open');
        detail.classList.toggle('open');
        this.textContent = isOpen ? '展开详情 +' : '收起详情 −';
      }
    });
  });

  // --- Contact form validation ---
  const form = document.querySelector('.form');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      let isValid = true;

      // Reset errors
      form.querySelectorAll('.form__group').forEach(function (group) {
        group.classList.remove('error');
      });

      // Required fields: 项目名称/项目地点/建筑面积/联系人/联系电话
      var requiredFields = [
        { name: 'projectName', label: '项目名称' },
        { name: 'projectLocation', label: '项目地点' },
        { name: 'buildingArea', label: '建筑面积' },
        { name: 'contactName', label: '联系人' },
        { name: 'contactPhone', label: '联系电话' }
      ];

      requiredFields.forEach(function (f) {
        var el = form.querySelector('[name="' + f.name + '"]');
        if (el && !el.value.trim()) {
          el.closest('.form__group').classList.add('error');
          isValid = false;
        }
      });

      // 手机号格式校验
      var phoneEl = form.querySelector('[name="contactPhone"]');
      if (phoneEl && phoneEl.value.trim()) {
        var phoneRegex = /^[\d\-\+\s]{7,20}$/;
        if (!phoneRegex.test(phoneEl.value.trim())) {
          phoneEl.closest('.form__group').classList.add('error');
          isValid = false;
        }
      }

      if (isValid) {
        var btn = form.querySelector('.btn--primary');
        var originalText = btn.textContent;
        btn.textContent = '提交中...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.7';

        // checkbox 多选拼成逗号分隔字符串
        var panelChecks = form.querySelectorAll('[name="panelType"]:checked');
        var panelTypes = [];
        for (var i = 0; i < panelChecks.length; i++) {
          panelTypes.push(panelChecks[i].value);
        }

        var formData = {
          projectName: (form.querySelector('[name="projectName"]') || {}).value || '',
          projectLocation: (form.querySelector('[name="projectLocation"]') || {}).value || '',
          buildingArea: (form.querySelector('[name="buildingArea"]') || {}).value || '',
          buildingType: (form.querySelector('[name="buildingType"]') || {}).value || '',
          panelType: panelTypes.join(','),
          panelThickness: (form.querySelector('[name="panelThickness"]') || {}).value || '',
          floors: (form.querySelector('[name="floors"]') || {}).value || '',
          contactName: (form.querySelector('[name="contactName"]') || {}).value || '',
          contactPhone: phoneEl ? phoneEl.value.trim() : '',
          estimatedCost: (form.querySelector('[name="estimatedCost"]') || {}).value || '',
          startDate: (form.querySelector('[name="startDate"]') || {}).value || '',
          remarks: (form.querySelector('[name="remarks"]') || {}).value || ''
        };

        fetch('api/submit.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(formData)
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            btn.textContent = '已提交，我们将尽快联系您 ✓';
            form.reset();
          } else {
            btn.textContent = data.message || '提交失败，请重试';
          }
          setTimeout(function () {
            btn.textContent = originalText;
            btn.style.pointerEvents = '';
            btn.style.opacity = '';
          }, 3000);
        })
        .catch(function () {
          btn.textContent = '网络错误，请稍后重试';
          setTimeout(function () {
            btn.textContent = originalText;
            btn.style.pointerEvents = '';
            btn.style.opacity = '';
          }, 3000);
        });
      }
    });
  }

  // --- Download modal logic ---
  var downloadBtn = document.getElementById('downloadBtn');
  var downloadModal = document.getElementById('downloadModal');
  var modalClose = document.getElementById('modalClose');
  var modalBody = document.getElementById('modalBody');

  if (downloadBtn && downloadModal && modalClose && modalBody) {
    function renderFileList(files) {
      if (!files || files.length === 0) {
        modalBody.innerHTML = '<p class="modal__loading">暂无下载文件</p>';
        return;
      }
      var list = '<ul class="modal__file-list">';
      files.forEach(function (file) {
        list +=
          '<a href="' + encodeURI(file.url) + '" download class="modal__file-item">' +
          '<svg class="modal__file-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
          '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>' +
          '<polyline points="14 2 14 8 20 8"/>' +
          '<line x1="16" y1="13" x2="8" y2="13"/>' +
          '<line x1="16" y1="17" x2="8" y2="17"/>' +
          '<polyline points="10 9 9 9 8 9"/>' +
          '</svg>' +
          '<div class="modal__file-info">' +
          '<div class="modal__file-name">' + file.name + '</div>' +
          '</div>' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--color-gold);flex-shrink:0;">' +
          '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>' +
          '<polyline points="7 10 12 15 17 10"/>' +
          '<line x1="12" y1="15" x2="12" y2="3"/>' +
          '</svg>' +
          '</a>';
      });
      list += '</ul>';
      modalBody.innerHTML = list;
    }

    function openModal() {
      downloadModal.classList.add('open');
      document.body.style.overflow = 'hidden';
      modalBody.innerHTML = '<p class="modal__loading">加载中…</p>';

      if (window.__AKO_DOWNLOADS__ && window.__AKO_DOWNLOADS__.length > 0) {
        renderFileList(window.__AKO_DOWNLOADS__);
      } else {
        fetch('api/list_downloads.php')
          .then(function (res) { return res.json(); })
          .then(function (data) {
            renderFileList(data.files);
          })
          .catch(function () {
            modalBody.innerHTML = '<p class="modal__loading">加载失败，请刷新重试</p>';
          });
      }
    }

    function closeModal() {
      downloadModal.classList.remove('open');
      document.body.style.overflow = '';
    }

    downloadBtn.addEventListener('click', openModal);
    modalClose.addEventListener('click', closeModal);
    downloadModal.addEventListener('click', function (e) {
      if (e.target === downloadModal) closeModal();
    });
  }

  // --- Smooth scroll for anchor links ---
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

});
