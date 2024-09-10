/*
****************************************************************************
* Recording Voice Message
****************************************************************************
*/
(function () {
  const MAX_RECORDING_TIME = 60;
  const AUDIO_TYPES = [
    { mimeType: "audio/webm;codecs=opus", extension: "webm" },
    { mimeType: "audio/webm", extension: "webm" },
    { mimeType: "audio/mp4", extension: "mp4" },
    { mimeType: "audio/mpeg", extension: "mp3" },
  ];

  let microphoneButton;
  let form;
  let recordingUI;
  let trashButton;
  let recordingProgressBar;
  let recordingTime;
  let stopButton;
  let sendRecordButton;

  let isRecording = false;
  let isPaused = false;
  let isStopping = false;
  let mediaRecorder = null;
  let audioChunks = [];
  let stream = null;
  let recordingInterval = null;
  let recordingSeconds = 0;
  let recorderType = null;

  function initializeDOMElements() {
    microphoneButton = document.getElementById("microphone-button");
    form = document.getElementById("message-form");
    recordingUI = document.getElementById("recording-ui");
    trashButton = document.getElementById("trash-button");
    recordingProgressBar = document.getElementById("recording-progress-bar");
    recordingTime = document.getElementById("recording-time");
    stopButton = document.getElementById("stop-button");
    sendRecordButton = document.getElementById("send-record-button");

    return [
      microphoneButton,
      form,
      recordingUI,
      trashButton,
      recordingProgressBar,
      recordingTime,
      stopButton,
      sendRecordButton,
    ].every(Boolean);
  }

  function canRecordAudio() {
    return !!(navigator.mediaDevices?.getUserMedia && window.MediaRecorder);
  }

  function getSupportedAudioType() {
    if (!window.MediaRecorder?.isTypeSupported) {
      return { mimeType: "", extension: "webm" };
    }

    return AUDIO_TYPES.find((type) => MediaRecorder.isTypeSupported(type.mimeType));
  }

  function setupEventListeners() {
    microphoneButton.addEventListener("click", toggleRecording);
    stopButton.addEventListener("click", togglePauseResume);
    sendRecordButton.addEventListener("click", sendRecording);
    trashButton.addEventListener("click", cancelRecording);
  }

  function toggleRecording(event) {
    event.preventDefault();

    if (isRecording || isPaused) {
      cancelRecording(event);
      return;
    }

    requestMicrophonePermission();
  }

  function requestMicrophonePermission() {
    if (!canRecordAudio()) {
      alert("Voice recording is not supported in this browser.");
      return;
    }

    recorderType = getSupportedAudioType();

    if (!recorderType) {
      alert("Voice recording is not supported in this browser.");
      return;
    }

    navigator.mediaDevices
      .getUserMedia({ audio: true })
      .then((audioStream) => {
        stream = audioStream;
        startRecording();
      })
      .catch((error) => {
        console.error("Error accessing microphone:", error);
        alert("Microphone permission is required to record audio.");
      });
  }

  function startRecording() {
    audioChunks = [];
    recordingSeconds = 0;
    isRecording = true;
    isPaused = false;
    isStopping = false;

    form.style.display = "none";
    recordingUI.style.display = "flex";
    microphoneButton.classList.add("recording");
    stopButton.innerHTML = '<span class="fas fa-pause"></span>';

    updateRecordingTime();
    updateProgressBar();

    const options = recorderType.mimeType ? { mimeType: recorderType.mimeType } : undefined;
    mediaRecorder = new MediaRecorder(stream, options);

    mediaRecorder.addEventListener("dataavailable", (event) => {
      if (event.data?.size > 0) {
        audioChunks.push(event.data);
      }
    });

    mediaRecorder.addEventListener("stop", () => {
      stopStream();
      isRecording = false;
      isPaused = false;
      isStopping = false;
      microphoneButton.classList.remove("recording");
      stopButton.innerHTML = '<span class="fas fa-pause"></span>';
    });

    mediaRecorder.start();
    startRecordingTimer();
  }

  function startRecordingTimer() {
    clearInterval(recordingInterval);
    recordingInterval = setInterval(() => {
      if (isPaused) {
        return;
      }

      recordingSeconds += 1;
      updateRecordingTime();
      updateProgressBar();

      if (recordingSeconds >= MAX_RECORDING_TIME) {
        finishRecording();
      }
    }, 1000);
  }

  function finishRecording(callback) {
    clearInterval(recordingInterval);

    if (!mediaRecorder || mediaRecorder.state === "inactive" || isStopping) {
      if (typeof callback === "function") {
        callback();
      }
      return;
    }

    isStopping = true;
    mediaRecorder.addEventListener(
      "stop",
      () => {
        if (typeof callback === "function") {
          callback();
        }
      },
      { once: true }
    );
    mediaRecorder.stop();
  }

  function togglePauseResume(event) {
    event.preventDefault();

    if (!mediaRecorder || !isRecording) {
      return;
    }

    if (isPaused) {
      mediaRecorder.resume();
      isPaused = false;
      stopButton.innerHTML = '<span class="fas fa-pause"></span>';
      return;
    }

    mediaRecorder.pause();
    isPaused = true;
    stopButton.innerHTML = '<span class="fas fa-play"></span>';
  }

  function sendRecording(event) {
    event.preventDefault();

    const duration = formatTime(recordingSeconds);

    finishRecording(() => {
      const audioBlob = new Blob(audioChunks, {
        type: recorderType?.mimeType || "audio/webm",
      });

      if (audioBlob.size > 0) {
        sendMessage(true, audioBlob, duration, `voice-message.${recorderType?.extension || "webm"}`);
      }

      resetRecordingState();
    });
  }

  function cancelRecording(event) {
    event?.preventDefault();
    finishRecording(resetRecordingState);
  }

  function resetRecordingState() {
    clearInterval(recordingInterval);
    audioChunks = [];
    recordingSeconds = 0;
    isRecording = false;
    isPaused = false;
    isStopping = false;
    mediaRecorder = null;

    updateRecordingTime();
    updateProgressBar();
    stopStream();

    form.style.display = "flex";
    recordingUI.style.display = "none";
    microphoneButton.classList.remove("recording");
    stopButton.innerHTML = '<span class="fas fa-pause"></span>';
  }

  function stopStream() {
    if (stream) {
      stream.getTracks().forEach((track) => track.stop());
      stream = null;
    }
  }

  function updateRecordingTime() {
    if (recordingTime) {
      recordingTime.textContent = formatTime(recordingSeconds);
    }
  }

  function updateProgressBar() {
    if (recordingProgressBar) {
      recordingProgressBar.style.width = `${(recordingSeconds / MAX_RECORDING_TIME) * 100}%`;
    }
  }

  function formatTime(time) {
    const minutes = Math.floor(time / 60);
    const seconds = Math.floor(time % 60);
    return `${minutes}:${seconds.toString().padStart(2, "0")}`;
  }

  function setPlayIcon(playButton, isPlaying) {
    const icon = playButton?.querySelector("i, svg");

    if (!icon) {
      return;
    }

    icon.classList.toggle("fa-play", !isPlaying);
    icon.classList.toggle("fa-pause", isPlaying);
    icon.setAttribute("data-icon", isPlaying ? "pause" : "play");
  }

  function initializeAudioPlayer(playerId) {
    const waveform = document.getElementById(`waveform-${playerId}`);
    const playButton = document.querySelector(`.btn-toggle-play[data-player-id="${playerId}"]`);

    if (!waveform || !playButton || waveform.classList.contains("initialized")) {
      return;
    }

    if (typeof WaveSurfer === "undefined") {
      console.error("WaveSurfer is not loaded.");
      return;
    }

    const wavesurfer = WaveSurfer.create({
      container: waveform,
      waveColor: "rgb(218, 210, 210)",
      progressColor: "grey",
      cursorColor: "transparent",
      barWidth: 2,
      barRadius: 3,
      height: 36,
      responsive: true,
      barGap: 3,
      hideScrollbar: true,
    });

    waveform.classList.add("initialized");
    wavesurfer.load(waveform.getAttribute("data-audio-url"));

    playButton.addEventListener("click", function () {
      wavesurfer.playPause();
    });

    wavesurfer.on("play", function () {
      setPlayIcon(playButton, true);
    });

    wavesurfer.on("pause", function () {
      setPlayIcon(playButton, false);
    });

    wavesurfer.on("finish", function () {
      setPlayIcon(playButton, false);
      wavesurfer.seekTo(0);
    });

    wavesurfer.on("ready", function () {
      const durationElement = document.querySelector(`#player-${playerId} .duration`);

      if (durationElement) {
        durationElement.textContent = formatTime(wavesurfer.getDuration());
      }
    });
  }

  function renderWavesurfers() {
    document.querySelectorAll(".waveform").forEach((waveform) => {
      initializeAudioPlayer(waveform.getAttribute("data-audio-id"));
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (!initializeDOMElements()) {
      return;
    }

    if (!canRecordAudio() || !getSupportedAudioType()) {
      microphoneButton.setAttribute("disabled", "disabled");
      microphoneButton.classList.add("btn-disabled");
      return;
    }

    setupEventListeners();
  });

  window.initializeAudioPlayer = initializeAudioPlayer;
  window.renderWavesurfers = renderWavesurfers;
  window.rederWavesurfers = renderWavesurfers;
})();
