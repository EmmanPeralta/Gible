// settings.js (unified + auto-sync across all pages)
document.addEventListener('DOMContentLoaded', () => {

  // Helper functions
  function applyAudio(volume, mute) {
    const audios = [
      document.getElementById('clickSound'),
      document.getElementById('closeSound'),
      document.getElementById('hoverSound'),
      document.getElementById('wrongSound')
    ];
    const vol = mute === 1 ? 0 : volume / 100;
    audios.forEach(audio => { if (audio) audio.volume = vol; });
  }

  function applyTTS(enabled) {
    window.ttsEnabled = enabled ? 1 : 0;
    localStorage.setItem('ttsEnabled', enabled ? 1 : 0);
  }

  function applyColorblind(enabled, type) {
    document.body.classList.remove('colorblind-green', 'colorblind-red');
    if (enabled === 1) {
      document.body.classList.add(type === 'red' ? 'colorblind-red' : 'colorblind-green');
    }
    localStorage.setItem('colorblindEnabled', enabled);
    localStorage.setItem('colorblindType', type || 'green');
  }


  // TTS setup
  let ttsVoice = null;
  const synth = window.speechSynthesis;

  function loadTTSVoice() {
    const voices = synth.getVoices();
    if (!voices.length) {
      // Retry until voices are loaded
      setTimeout(loadTTSVoice, 200);
      return;
    }

    // Female TTS voice
    ttsVoice =
      voices.find(v =>
        (/female|woman|teacher/i.test(v.name + v.voiceURI)) && /en/i.test(v.lang)
      ) ||
      voices.find(v => /en/i.test(v.lang)) ||
      voices[0];
  }

  synth.onvoiceschanged = loadTTSVoice;
  loadTTSVoice();

  function speakText(text) {
    if (window.ttsEnabled !== 1 || !text) return;

    // Cancel any ongoing speech to prevent overlap
    synth.cancel();

    const utter = new SpeechSynthesisUtterance(text);
    utter.voice = ttsVoice;
    utter.rate = 0.93;   // calm pacing
    utter.pitch = 1.05;  // teacher-like tone
    utter.volume = 1;
    utter.lang = 'en-US';

    synth.speak(utter);
  }

  // Allow other scripts (e.g., sliders) to trigger TTS manually
  window.manualTTSSpeak = function(text) {
    speakText(text);
  };

  // Detects page type
  const hasSettingsUI = document.getElementById('sfx-volume') !== null;

  // UI references
  const sfxSlider = document.getElementById('sfx-volume');
  const sfxPercent = document.getElementById('sfx-percent');
  const muteCheckbox = document.getElementById('mute-all');
  const ttsCheckbox = document.getElementById('tts-toggle');
  const cbToggle = document.getElementById('colorblind-toggle');
  const cbType = document.getElementById('colorblind-type');
  const cbTypeRow = document.getElementById('colorblind-type-row');
  const saveBtn = document.getElementById('save-btn');
  const notification = document.getElementById('notification');
  const closeBtn = document.querySelector('.settings-btn[href="#"]');

  // Load settings straight from Database
  fetch('settings_load.php')
    .then(res => res.ok ? res.json() : Promise.reject())
    .then(data => {
      if (data.status === "success") {
        localStorage.setItem('userVolume', data.volume);
        localStorage.setItem('userMute', data.mute);
        localStorage.setItem('ttsEnabled', data.tts_enabled);
        localStorage.setItem('colorblindEnabled', data.colorblind_enabled);
        localStorage.setItem('colorblindType', data.colorblind_type || 'green');
      }
    })
    .catch(() => console.warn("⚠️ Settings load failed, using localStorage defaults."))
    .finally(() => {
      if (hasSettingsUI) initSettingsUI();
      else applySettingsOnly();
    });

  // Apply for Non-Settings pages
  function applySettingsOnly() {
    const vol = parseInt(localStorage.getItem('userVolume')) || 100;
    const mute = parseInt(localStorage.getItem('userMute')) || 0;
    const tts = localStorage.getItem('ttsEnabled') === '1' ? 1 : 0;
    const cbEnabled = localStorage.getItem('colorblindEnabled') === '1' ? 1 : 0;
    const cbTypeVal = localStorage.getItem('colorblindType') || 'green';
    applyAudio(vol, mute);
    applyTTS(tts);
    applyColorblind(cbEnabled, cbTypeVal);
  }

  // Settings (Page Logic)
  function initSettingsUI() {
    let savedVolume = parseInt(localStorage.getItem('userVolume')) || 100;
    let savedMute = parseInt(localStorage.getItem('userMute')) || 0;
    let savedTTS = localStorage.getItem('ttsEnabled') === '1' ? 1 : 0;
    let savedCBEnabled = localStorage.getItem('colorblindEnabled') === '1';
    let savedCBType = localStorage.getItem('colorblindType') || 'green';

    function showNotice(msg) {
      notification.innerText = msg;
      notification.style.display = 'block';
      setTimeout(() => notification.style.display = 'none', 2000);
    }

    // Initial UI
    muteCheckbox.checked = savedMute === 1;
    sfxSlider.value = savedMute === 1 ? 0 : savedVolume;
    sfxSlider.disabled = savedMute === 1;
    sfxPercent.textContent = sfxSlider.value + "%";
    ttsCheckbox.checked = savedTTS === 1;
    cbToggle.checked = savedCBEnabled;
    cbType.value = savedCBType;
    cbTypeRow.style.display = savedCBEnabled ? 'flex' : 'none';
    applySettingsOnly();

    // Live events
    sfxSlider.addEventListener('input', () => {
      if (!muteCheckbox.checked) {
        const newVol = parseInt(sfxSlider.value);
        applyAudio(newVol, 0);
        sfxPercent.textContent = newVol + "%";
        localStorage.setItem('userVolume', newVol);
        localStorage.setItem('userMute', 0);
      }
    });

    muteCheckbox.addEventListener('change', () => {
      const isMuted = muteCheckbox.checked;
      sfxSlider.disabled = isMuted;
      sfxSlider.value = isMuted ? 0 : savedVolume;
      sfxPercent.textContent = sfxSlider.value + "%";
      applyAudio(parseInt(sfxSlider.value), isMuted ? 1 : 0);
      localStorage.setItem('userVolume', parseInt(sfxSlider.value));
      localStorage.setItem('userMute', isMuted ? 1 : 0);
    });

    ttsCheckbox.addEventListener('change', () => {
      const state = ttsCheckbox.checked ? 1 : 0;
      applyTTS(state);
      showNotice(state ? "TTS Enabled" : "TTS Disabled");
    });

    cbToggle.addEventListener('change', () => {
      const enabled = cbToggle.checked ? 1 : 0;
      cbTypeRow.style.display = enabled ? 'flex' : 'none';
      applyColorblind(enabled, enabled ? cbType.value : null);
    });

    cbType.addEventListener('change', () => {
      if (cbToggle.checked) applyColorblind(1, cbType.value);
    });

    // Save
    saveBtn.addEventListener('click', () => {
      const vol = parseInt(sfxSlider.value);
      const mute = muteCheckbox.checked ? 1 : 0;
      const tts = ttsCheckbox.checked ? 1 : 0;
      const cbEnabled = cbToggle.checked ? 1 : 0;
      const cbTypeVal = cbToggle.checked ? cbType.value : 'green';

      localStorage.setItem('userVolume', vol);
      localStorage.setItem('userMute', mute);
      localStorage.setItem('ttsEnabled', tts);
      localStorage.setItem('colorblindEnabled', cbEnabled);
      localStorage.setItem('colorblindType', cbTypeVal);

      fetch('settings_save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          volume: vol,
          mute: mute,
          tts_enabled: tts,
          colorblind_enabled: cbEnabled,
          colorblind_type: cbTypeVal
        })
      })
        .then(() => showNotice("Changes applied & saved"))
        .catch(() => showNotice("Save failed"));
    });
  }

  // All Pages Live-sync
  window.addEventListener('storage', (e) => {
    if (!e.key) return;
    if (['userVolume', 'userMute', 'ttsEnabled', 'colorblindEnabled', 'colorblindType'].includes(e.key)) {
      applySettingsOnly();
    }
  });


  // Hover-based TTS
  document.body.addEventListener('mouseover', (e) => {
    if (window.ttsEnabled !== 1) return;
    const el = e.target.closest('button, a, .settings-btn, .tab, p, .speak-on-hover');
    if (!el) return;
    if (el.dataset && (el.dataset.ttsTrigger === 'click' || el.dataset.ttsSkip === '1')) return;
    const text = el.dataset.label || el.innerText || el.getAttribute('aria-label');
    if (text) speakText(text.trim());
  }, true);
});
