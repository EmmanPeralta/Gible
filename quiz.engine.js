(function(){
  // Colorblind settings and color profiles
  const cbEnabled = localStorage.getItem('colorblindEnabled') === '1';
  const cbType = localStorage.getItem('colorblindType') || 'green';

  const COLOR_PROFILES = {
    normal: {
      correct: "#28a745",
      wrong: "#f04757"
    },
    red: {
      correct: "#ff9966",
      wrong: "#7fd9ff"
    },
    green: {
      correct: "#ff7799",
      wrong: "#0092c7"
    }
  };
  let ACTIVE_MODE = "normal";
  if (cbEnabled) ACTIVE_MODE = cbType === "red" ? "red" : "green";

  window.COLOR_PROFILES = COLOR_PROFILES;
  window.getColor = function(which){
    return COLOR_PROFILES[ACTIVE_MODE][which];
  };

  // TTS helpers for analysis
  window.shapeNames = ['Triangle', 'Circle', 'Cross', 'Square'];
  window.extractCleanAnswer = function(opt, idx){
    if (/<img/i.test(opt)) return 'image ' + (idx + 1);
    const dashIdx = opt.indexOf('-');
    const clean = dashIdx !== -1 ? opt.substring(dashIdx + 1).trim() : opt.trim();
    return clean;
  };
  window.optionToTTSWithShape = function(optHtml, optIndex){
    const clean = window.extractCleanAnswer(optHtml, optIndex);
    const shape = window.shapeNames[optIndex] || ('option ' + (optIndex + 1));
    return shape + ' for. ' + clean;
  };

  // Celebration animation
  window.showCelebration = function(){
    const confetti = document.createElement('div');
    confetti.className = 'confetti-container';
    document.body.appendChild(confetti);

    const shapes = ['△', '○', '✕', '□'];
    const colors = ['#0072B2', '#56B4E9', '#E69F00', '#F0E442', '#CC79A7', '#009E73', '#8DA0CB'];

    for (let i = 0; i < 35; i++) {
      const span = document.createElement('span');
      span.textContent = shapes[Math.floor(Math.random() * shapes.length)];
      span.className = 'confetti';
      span.style.left = Math.random() * 100 + 'vw';
      span.style.fontSize = (Math.random() * 1.5 + 1) + 'rem';
      span.style.color = colors[Math.floor(Math.random() * colors.length)];
      span.style.animationDelay = Math.random() + 's';
      confetti.appendChild(span);
    }

    setTimeout(() => confetti.remove(), 4000);
  };

  // Convenience wrappers for inline onclicks in HTML
  window.selectItem = function(name){
    const el = document.getElementById('item-' + name);
    if (el && typeof el.click === 'function') el.click();
  };
  window.selectPowerup = function(key){
    const slotNum = key === 'powerup1' ? 1 : key === 'powerup2' ? 2 : null;
    if (!slotNum) return;
    const el = document.getElementById('powerup-' + slotNum);
    if (el && typeof el.click === 'function') el.click();
  };

  // Items and Power-ups initialization
  // Root path for assets and backend endpoints. Using absolute path avoids
  // relative traversal issues from nested quiz directories (e.g. fa-skills/math/).
  var QUIZ_ROOT = '/b/';

  window.initItemsAndPowerups = function(potionQty, scoreUpQty, guardUpQty){
    window.items = {
      potion: { qty: Math.min(potionQty || 0, 1), desc: "Potion. Adds 10 KP during quiz. Click to use." },
      score_up: { qty: Math.min(scoreUpQty || 0, 1), desc: "Score Up. Correct answer gives 15 KP instead of 10 KP. Click to use." },
      guard_up: { qty: Math.min(guardUpQty || 0, 1), desc: "Guard Up. Wrong answer takes only 5 KP instead of 10 KP. Click to use." }
    };
    window.allPowerups = [
      { key: 'fifty_fifty', img: QUIZ_ROOT + 'images/50-50.png', desc: '50/50. Removes two incorrect answers. Click to use.' },
      { key: 'peek', img: QUIZ_ROOT + 'images/peek.png', desc: 'Peek. Highlights the correct answer. Click to use.' },
      { key: 'shield', img: QUIZ_ROOT + 'images/shield.png', desc: 'Shield. Wrong answer takes 0 KP. Click to use.' },
      { key: 'triple_points', img: QUIZ_ROOT + 'images/triple_points.png', desc: 'Triple Points. Correct answer gives 30 KP. Click to use.' }
    ];
    window.powerups = {};
    window.powerupSlots = [null, null];
    window.potionUsedThisQuestion = false;
    window.scoreUpUsedThisQuestion = false;
    window.guardUpUsedThisQuestion = false;
    window.itemOrPowerupUsedThisQuestion = false;

    function rollPowerup(slot){
      let available = allPowerups.filter(function(p){
        return (powerupSlots[0] ? powerupSlots[0].key !== p.key : true) && (powerupSlots[1] ? powerupSlots[1].key !== p.key : true);
      });
      let idx = Math.floor(Math.random() * available.length);
      powerupSlots[slot] = available[idx];
      const el = document.getElementById('powerup-' + (slot + 1));
      if (el){ el.innerHTML = '<img src="' + available[idx].img + '" alt="" style="width:48px;height:48px;">'; }
      powerups['powerup' + (slot + 1)] = Object.assign({}, available[idx], { used: false });
    }
    window.rollPowerup = rollPowerup;

    function updateQuantities(){
      const qp = document.getElementById('qty-potion'); if (qp) qp.textContent = 'x' + items.potion.qty;
      const qs = document.getElementById('qty-scoreup'); if (qs) qs.textContent = 'x' + items.score_up.qty;
      const qg = document.getElementById('qty-guardup'); if (qg) qg.textContent = 'x' + items.guard_up.qty;
    }
    window.updateQuantities = updateQuantities;

    function setupItemHover(){
      ['potion', 'score_up', 'guard_up'].forEach(function(item){
        let el = document.getElementById('item-' + item);
        if (!el) return;
        el.onmouseenter = function(){
          const d = document.getElementById('desc-row'); if (d) d.textContent = items[item].desc;
        };
        el.onmouseleave = function(){
          const d = document.getElementById('desc-row'); if (d) d.textContent = "You may use an item or power-up";
        };
      });
    }
    window.setupItemHover = setupItemHover;

    function setupPowerupHover(){
      [1, 2].forEach(function(slotNum){
        let el = document.getElementById('powerup-' + slotNum);
        if (!el) return;
        el.onmouseenter = function(){
          let p = powerups['powerup' + slotNum];
          if (p && !p.used){
            const d = document.getElementById('desc-row'); if (d) d.textContent = p.desc;
          }
        };
        el.onmouseleave = function(){
          const d = document.getElementById('desc-row'); if (d) d.textContent = "You may use an item or power-up";
        };
      });
    }
    window.setupPowerupHover = setupPowerupHover;

    function setupItemClick(){
      function disableOtherItemsAndPowerups(){
        ['item-potion', 'item-score_up', 'item-guard_up'].forEach(function(id){
          const el = document.getElementById(id);
          if (el){
            const img = el.querySelector('img'); if (img) img.style.opacity = '0.35';
            el.style.pointerEvents = 'none';
          }
        });
        [1,2].forEach(function(slotNum){
          const el = document.getElementById('powerup-' + slotNum);
          if (el){
            const img = el.querySelector('img'); if (img) img.style.opacity = '0.35';
            el.style.pointerEvents = 'none';
          }
        });
      }
      const potionEl = document.getElementById('item-potion');
      if (potionEl){
        potionEl.onclick = function(){
          if (!potionUsedThisQuestion && items.potion.qty > 0 && !itemOrPowerupUsedThisQuestion){
            potionUsedThisQuestion = true; itemOrPowerupUsedThisQuestion = true; disableOtherItemsAndPowerups();
            useItemBackend('potion', function(){
              window.kp = (typeof kp === 'number' ? kp : 100) + 10;
              items.potion.qty = 0; updateQuantities();
              const ka = document.getElementById('kp-amount'); if (ka) ka.textContent = window.kp;
              setTimeout(function(){ const d = document.getElementById('desc-row'); if (d) d.textContent = "Potion used! +10 KP!"; }, 0);
              if (typeof speak === 'function' && (typeof ttsEnabled === 'undefined' || ttsEnabled === 1)) speak("Potion used! Plus 10 KP.");
            });
          }
        };
      }
      const scoreEl = document.getElementById('item-score_up');
      if (scoreEl){
        scoreEl.onclick = function(){
          if (!scoreUpUsedThisQuestion && items.score_up.qty > 0 && !itemOrPowerupUsedThisQuestion){
            scoreUpUsedThisQuestion = true; itemOrPowerupUsedThisQuestion = true; disableOtherItemsAndPowerups();
            useItemBackend('score_up', function(){
              items.score_up.qty = 0; updateQuantities();
              setTimeout(function(){ const d = document.getElementById('desc-row'); if (d) d.textContent = "Score Up activated! Next correct answer gives 15 KP."; }, 0);
              if (typeof speak === 'function' && (typeof ttsEnabled === 'undefined' || ttsEnabled === 1)) speak("Score Up activated! Next correct answer gives 15 KP.");
            });
          }
        };
      }
      const guardEl = document.getElementById('item-guard_up');
      if (guardEl){
        guardEl.onclick = function(){
          if (!guardUpUsedThisQuestion && items.guard_up.qty > 0 && !itemOrPowerupUsedThisQuestion){
            guardUpUsedThisQuestion = true; itemOrPowerupUsedThisQuestion = true; disableOtherItemsAndPowerups();
            useItemBackend('guard_up', function(){
              items.guard_up.qty = 0; updateQuantities();
              setTimeout(function(){ const d = document.getElementById('desc-row'); if (d) d.textContent = "Guard Up activated! Next wrong answer loses only 5 KP."; }, 0);
              if (typeof speak === 'function' && (typeof ttsEnabled === 'undefined' || ttsEnabled === 1)) speak("Guard Up activated! Next wrong answer loses only 5 KP.");
            });
          }
        };
      }
    }
    window.setupItemClick = setupItemClick;

    function setupPowerupClick(){
      function disableOtherItemsAndPowerups(){
        ['item-potion', 'item-score_up', 'item-guard_up'].forEach(function(id){
          const el = document.getElementById(id);
          if (el){ const img = el.querySelector('img'); if (img) img.style.opacity = '0.35'; el.style.pointerEvents = 'none'; }
        });
        [1,2].forEach(function(slotNum){
          const el = document.getElementById('powerup-' + slotNum);
          if (el){ const img = el.querySelector('img'); if (img) img.style.opacity = '0.35'; el.style.pointerEvents = 'none'; }
        });
      }

      [1, 2].forEach(function(slotNum){
        let el = document.getElementById('powerup-' + slotNum);
        if (!el) return;
        el.onclick = function(){
          let powerup = 'powerup' + slotNum;
          if (!powerups[powerup] || powerups[powerup].used || itemOrPowerupUsedThisQuestion) return;
          let p = powerups[powerup];
          let ttsMsg = '';
          disableOtherItemsAndPowerups();
          if (p.key === 'fifty_fifty'){
            let correct = window.questions ? window.questions[window.currentQuestion].correct : null;
            let removed = 0;
            document.querySelectorAll('.answer-btn').forEach(function(btn, idx){
              if (idx !== correct && removed < 2){ btn.style.background = '#222'; btn.disabled = true; removed++; }
            });
            setTimeout(function(){ const d = document.getElementById('desc-row'); if (d) d.textContent = "Two wrong answers removed!"; }, 0);
            ttsMsg = "Two wrong answers removed!";
          }
          if (p.key === 'peek'){
            let correct = window.questions ? window.questions[window.currentQuestion].correct : null;
            document.querySelectorAll('.answer-btn').forEach(function(btn, idx){ btn.style.border = idx === correct ? '3px solid var(--ans-highlight)' : ''; });
            setTimeout(function(){ const d = document.getElementById('desc-row'); if (d) d.textContent = "Correct answer highlighted!"; }, 0);
            ttsMsg = "Correct answer highlighted!";
          }
          if (p.key === 'shield'){
            powerups[powerup].shieldActive = true;
            setTimeout(function(){ const d = document.getElementById('desc-row'); if (d) d.textContent = "Shield active!"; }, 0);
            ttsMsg = "Shield active!";
          }
          if (p.key === 'triple_points'){
            powerups[powerup].tripleActive = true;
            setTimeout(function(){ const d = document.getElementById('desc-row'); if (d) d.textContent = "Triple Points active!"; }, 0);
            ttsMsg = "Triple Points active!";
          }
          powerups[powerup].used = true;
          powerupSlots[slotNum - 1] = null;
          el.innerHTML = '';
          el.removeAttribute('data-label');
          itemOrPowerupUsedThisQuestion = true;
          if (ttsMsg && typeof speak === 'function' && (typeof ttsEnabled === 'undefined' || ttsEnabled === 1)){
            if (window.speechSynthesis) speechSynthesis.cancel();
            speak(ttsMsg);
          }
        };
      });
    }
    window.setupPowerupClick = setupPowerupClick;

    window.useItemBackend = function(item, callback){
      fetch(QUIZ_ROOT + 'use_item.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'item=' + encodeURIComponent(item)
      })
      .then(function(response){ return response.ok ? response.text() : Promise.reject('Error'); })
      .then(function(newQty){
        items[item].qty = parseInt(newQty, 10);
        updateQuantities();
        if (callback) callback();
      })
      .catch(function(){
        // Keep user informed but indicate it is a backend/path issue.
        alert('Failed to use item (backend unreachable).');
        if (callback) callback();
      });
    };

    function handlePowerupRolling(){
      if (window.currentQuestion === 0 && !powerupSlots[0]) rollPowerup(0);
      if (window.currentQuestion === 5 && !powerupSlots[1]) rollPowerup(1);
    }
    window.handlePowerupRolling = handlePowerupRolling;

    // Global answer handler used by quiz pages
    window.answerQuestion = function(selectedIndex){
      if (!window.questions || typeof window.currentQuestion !== 'number') return;
      var qIdx = window.currentQuestion;
      if (qIdx >= window.questions.length) return;

      var correctIndex = window.questions[qIdx].correct;
      var delta = selectedIndex === correctIndex ? 10 : -10;

      // Apply item effects
      if (selectedIndex === correctIndex) {
        // Triple Points overrides other increases
        var tripleActive = (window.powerups && (
          (window.powerups.powerup1 && window.powerups.powerup1.tripleActive) ||
          (window.powerups.powerup2 && window.powerups.powerup2.tripleActive)
        ));
        if (tripleActive) {
          delta = 30;
        } else if (window.scoreUpUsedThisQuestion) {
          delta = 15;
        }
      } else {
        var shieldActive = (window.powerups && (
          (window.powerups.powerup1 && window.powerups.powerup1.shieldActive) ||
          (window.powerups.powerup2 && window.powerups.powerup2.shieldActive)
        ));
        if (shieldActive) {
          delta = 0;
        } else if (window.guardUpUsedThisQuestion) {
          delta = -5;
        }
      }

      // Update KP
      if (typeof window.kp !== 'number') window.kp = 100;
      window.kp += delta;
      var kpEl = document.getElementById('kp-amount');
      if (kpEl) kpEl.textContent = window.kp;

      // Sound effects (correct vs wrong)
      try {
        var correctAudio = document.getElementById('clickSound');
        var wrongAudio = document.getElementById('wrongSound');
        if (selectedIndex === correctIndex) {
          if (correctAudio){ correctAudio.currentTime = 0; correctAudio.play().catch(function(){}); }
        } else {
          if (wrongAudio){ wrongAudio.currentTime = 0; wrongAudio.play().catch(function(){}); }
        }
      } catch(e) { /* fail silently */ }

      // Visual feedback on buttons
      var buttons = document.querySelectorAll('.answer-btn');
      buttons.forEach(function(btn, idx){
        btn.disabled = true;
        if (idx === correctIndex) {
          btn.style.background = getColor('correct');
        } else if (idx === selectedIndex) {
          btn.style.background = getColor('wrong');
        }
      });

      // Track results
      if (!Array.isArray(window.userAnswers)) window.userAnswers = [];
      if (!Array.isArray(window.questionResults)) window.questionResults = [];
      window.userAnswers.push(selectedIndex);
      var opts = window.questions[qIdx].options || [];
      window.questionResults.push({
        userIndex: selectedIndex,
        correctIndex: correctIndex,
        userOptionHtml: opts[selectedIndex] || '',
        correctOptionHtml: opts[correctIndex] || ''
      });
      if (selectedIndex === correctIndex) {
        window.correctAnswers = (typeof window.correctAnswers === 'number' ? window.correctAnswers : 0) + 1;
      }

      // Reset one-time flags and re-enable item/powerup interactions for next question
      window.potionUsedThisQuestion = false;
      window.scoreUpUsedThisQuestion = false;
      window.guardUpUsedThisQuestion = false;
      window.itemOrPowerupUsedThisQuestion = false;
      if (window.powerups) {
        ['powerup1','powerup2'].forEach(function(key){
          if (window.powerups[key]) {
            delete window.powerups[key].tripleActive;
            delete window.powerups[key].shieldActive;
          }
        });
      }
      ['item-potion','item-score_up','item-guard_up','powerup-1','powerup-2'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) { el.style.pointerEvents = ''; var img = el.querySelector('img'); if (img) img.style.opacity = '1'; }
      });
      var msgElem = document.getElementById('item-powerup-msg'); if (msgElem) msgElem.remove();

      // Next question
      window.currentQuestion = qIdx + 1;
      setTimeout(function(){ if (typeof window.showQuestion === 'function') window.showQuestion(); }, 250);
    };

    // Initial UI setup
    const desc = document.getElementById('desc-row'); if (desc) desc.textContent = "You may use an item or power-up";
    updateQuantities();
    setupItemHover();
    setupPowerupHover();
    setupItemClick();
    setupPowerupClick();

    // Patch showQuestion to also handle power-up rolling
    if (typeof window.showQuestion === 'function'){
      const origShowQuestion = window.showQuestion;
      window.showQuestion = function(){
        handlePowerupRolling();
        origShowQuestion();
        setupPowerupHover();
        setupPowerupClick();
        setupItemClick();
        // If current question has no images in question text or options,
        // auto-scroll the vertical overflow to the bottom to focus answers/items.
        try {
          var qIdx = (typeof window.currentQuestion === 'number') ? window.currentQuestion : 0;
          var q = (Array.isArray(window.questions) && window.questions[qIdx]) ? window.questions[qIdx] : null;
          var hasImg = false;
          if (q) {
            var qText = (q.question || q.questionHtml || '').toString();
            var qOpts = Array.isArray(q.options) ? q.options : [];
            hasImg = /<img/i.test(qText) || qOpts.some(function(o){ return /<img/i.test(o); });
          }
          if (!hasImg) {
            setTimeout(function(){
              var se = document.scrollingElement || document.documentElement || document.body;
              try {
                se.scrollTo({ top: se.scrollHeight, behavior: 'auto' });
              } catch(e) {
                se.scrollTop = se.scrollHeight;
              }
            }, 0);
          }
        } catch(e) {}
        const msgElem = document.getElementById('item-powerup-msg');
        if (itemOrPowerupUsedThisQuestion){
          if (!msgElem){
            const newMsg = document.createElement('div');
            newMsg.id = 'item-powerup-msg';
            newMsg.style.color = '#c00';
            newMsg.style.fontWeight = 'bold';
            newMsg.style.margin = '8px 0';
            newMsg.textContent = 'Please answer the question first before using another item or power-up.';
            const kpRow = document.querySelector('.kp-row');
            if (kpRow) kpRow.insertAdjacentElement('afterend', newMsg);
          }
        } else {
          if (msgElem) msgElem.remove();
        }
        if (typeof window.attachTTSListeners === 'function') window.attachTTSListeners();
      };
    }

    // Trigger first question render
    if (typeof window.showQuestion === 'function') window.showQuestion();
  };
})();