/*
****************************************************************************
* Recording Voice Message
****************************************************************************
*/
// Global variables
let microphoneButton, form, recordingUI, trashButton, recordingProgressBar, recordingTime, stopButton, sendRecordButton;
let isRecording = false;
let isPaused = false;
let mediaRecorder;
let audioChunks = [];
let stream;
let recordingInterval;
let recordingSeconds = 0;
const MAX_RECORDING_TIME = 60; // 1 minute

function initializeDOMElements() {
    microphoneButton = document.getElementById('microphone-button');
    form = document.getElementById('message-form');
    recordingUI = document.getElementById('recording-ui');
    trashButton = document.getElementById('trash-button');
    recordingProgressBar = document.getElementById('recording-progress-bar');
    recordingTime = document.getElementById('recording-time');
    stopButton = document.getElementById('stop-button');
    sendRecordButton = document.getElementById('send-record-button');
    const elements = [microphoneButton, form, recordingUI, trashButton, recordingProgressBar, recordingTime, stopButton, sendRecordButton];
    const missingElements = elements.filter(el => !el);

    if (missingElements.length > 0) {
        console.error('Some DOM elements are missing:', missingElements);
        return false;
    }

    return true;
}
/**
 *-------------------------------------------------------------
 * Recording Event
 *-------------------------------------------------------------
 */

function setupEventListeners() {
    microphoneButton.addEventListener('click', toggleRecording);
    stopButton.addEventListener('click', togglePauseResume);
    sendRecordButton.addEventListener('click', sendRecording);
    trashButton.addEventListener('click', cancelRecording);
}

/**
 *-------------------------------------------------------------
 * Toggle Record Button
 *-------------------------------------------------------------
 */

function toggleRecording() {
    if (isRecording) {
        stopRecording();
    } else {
        requestMicrophonePermission();
    }
}
/**
 *-------------------------------------------------------------
 * Request Microphone Permission
 *-------------------------------------------------------------
 */


function requestMicrophonePermission() {
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(audioStream => {
            stream = audioStream;
            startRecording();
        })
        .catch(error => {
            console.error('Error accessing microphone:', error);
            alert('Microphone permission is required to record audio.');
        });
}

/**
 *-------------------------------------------------------------
 * Start Recording
 *-------------------------------------------------------------
 */


function startRecording() {
    if (!initializeDOMElements()) {
        console.error('Cannot start recording due to missing DOM elements');
        return;
    }

    form.style.display ='none';
    recordingUI.style.display = 'flex';

    if (!isRecording) {
        recordingSeconds = 0;
        audioChunks = [];
    }
    updateRecordingTime();
    updateProgressBar();

    recordingInterval = setInterval(() => {
        if (!isPaused) {
            recordingSeconds++;
            updateRecordingTime();
            updateProgressBar();
            if (recordingSeconds >= MAX_RECORDING_TIME) {
                stopRecording();
            }
        }
    }, 1000);

    microphoneButton.classList.add('recording');
    stopButton.innerHTML = '<span class="fas fa-stop"></span>';

    const options = { mimeType: 'audio/webm' };
    mediaRecorder = new MediaRecorder(stream, options);

    mediaRecorder.start();

    mediaRecorder.addEventListener("dataavailable", event => {
        audioChunks.push(event.data);

    });

    isRecording = true;
    isPaused = false;
}

/**
 *-------------------------------------------------------------
 * Stop Recording
 *-------------------------------------------------------------
 */


function stopRecording() {
    if (!isRecording) return;

    clearInterval(recordingInterval);

    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
    }

    isRecording = false;
    isPaused = false;
    microphoneButton.classList.remove('recording');
    stopButton.innerHTML = '<span class="fas fa-stop"></span>';
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
}

/**
 *-------------------------------------------------------------
 * Toggle between pause and resume recording
 *-------------------------------------------------------------
 */


function togglePauseResume() {
    if (!isRecording) return;

    if (isPaused) {
        // Resume recording
        mediaRecorder.resume();
        stopButton.innerHTML = '<span class="fas fa-play"></span>';
        isPaused = false;
    } else {
        // Pause recording
        mediaRecorder.pause();
        stopButton.innerHTML = '<span class="fas fa-stop"></span>';
        isPaused = true;
    }
}
/**
 *-------------------------------------------------------------
 * Send Recording
 *-------------------------------------------------------------
 */


function sendRecording() {
  stopRecording(); 
  setTimeout(() => {

      if (audioChunks.length > 0) {
          const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });

          if (audioBlob.size > 0) {
              const duration = formatTime(recordingSeconds);
              sendMessage(true, audioBlob, duration);
          } else {
              console.warn('Blob size is zero.');
          }
      } else {
          console.warn('No audio data to send.');
      }

      resetRecordingState();
  }, 500); // delay 
}

/**
 *-------------------------------------------------------------
 * Canceling Recording
 *-------------------------------------------------------------
 */

function cancelRecording() {
    stopRecording();
    resetRecordingState();
}


/**
 *-------------------------------------------------------------
 * Reset Recording State
 *-------------------------------------------------------------
 */

function resetRecordingState() {
    audioChunks = [];
    recordingSeconds = 0;
    updateRecordingTime();
    updateProgressBar();
    form.style.display ='flex';
    recordingUI.style.display = 'none';
    isRecording = false;
    isPaused = false;
    stopButton.innerHTML = '<span class="fas fa-stop"></span>';
}

/**
 *-------------------------------------------------------------
 * update Recording Time
 *-------------------------------------------------------------
 */

function updateRecordingTime() {
    const minutes = Math.floor(recordingSeconds / 60).toString().padStart(2, '0');
    const seconds = (recordingSeconds % 60).toString().padStart(2, '0');
    recordingTime.textContent = `${minutes}:${seconds}`;
}

/**
 *-------------------------------------------------------------
 * update Progress Bar
 *-------------------------------------------------------------
 */

function updateProgressBar() {
    const progress = (recordingSeconds / MAX_RECORDING_TIME) * 100;
    recordingProgressBar.style.width = `${progress}%`;
}

/**
 *-------------------------------------------------------------
 * Format Time
 *-------------------------------------------------------------
 */


function formatTime(time) {
    const minutes = Math.floor(time / 60);
    const seconds = Math.floor(time % 60);
    return `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
}


document.addEventListener('DOMContentLoaded', function() {
    if (initializeDOMElements()) {
        setupEventListeners();
    } else {
        console.error('Failed to initialize audio recording feature due to missing DOM elements');
    }
});
/*
*-------------------------------------------------------------
* Function to initialize the audio player after a message is injected
*-------------------------------------------------------------
*/
function initializeAudioPlayer(selector, playerId) {
  if ($(`#waveform-${playerId}`).hasClass('initialized')) {
    return;
  }

  const container = $(selector);
  if (container.length === 0) {
    console.error(`Container element not found: ${selector}`);
    return;
  }

  let audioElement = document.querySelector(`#waveform-${playerId}`);
  const playButton = document.querySelector(`button[data-player-id="${playerId}"]`);
  initilaizeWavesurfer(audioElement,audioElement.getAttribute('data-audio-url'),playButton,`${playerId}`)
  // Mark the player as initialized
  $(`#waveform-${playerId}`).addClass('initialized');
}
/**
 *-------------------------------------------------------------
 * initilaize wavesurfer
 *-------------------------------------------------------------
 */
 function initilaizeWavesurfer(container,URL,playButton,playerId){
  let wavesurfer = WaveSurfer.create({
      container: container,
      waveColor: 'rgb(218, 210, 210)',
      progressColor: 'grey', 
      cursorColor: 'transparent', // No cursor
      barWidth: 2.5,
      barRadius: 3, 
      height: 28, 
      responsive: true,
      barGap:3,
    //   fillParent:true,
      hideScrollbar: true,
      drawingContextAttributes: {desynchronized: true}	,
      barMinHeight:5
      	
  });

  wavesurfer.load(URL);
      // Closure to ensure each playButton is associated with the correct wavesurfer instance
      playButton.addEventListener('click', function () {

          wavesurfer.playPause(); // Correctly refers to the associated wavesurfer instance
          const svg = playButton.querySelector('svg');

          if (svg) {
              // Check the current icon and toggle between play and pause
              if (svg.classList.contains('fa-play')) {
                  svg.classList.remove('fa-play');
                  svg.classList.add('fa-pause');
                  svg.setAttribute('data-icon', 'pause');
              } else {
                  svg.classList.remove('fa-pause');
                  svg.classList.add('fa-play');
                  svg.setAttribute('data-icon', 'play');
              }
          }
        });

  wavesurfer.on('ready', function () {
      var durationElement = document.querySelector("#player-" + playerId + " .duration");
      if (durationElement) {
          durationElement.textContent = formatTime(wavesurfer.getDuration());
      }
  });

}

/**
 *-------------------------------------------------------------
 * Reder Wavesurfers for Fetching Voice Messages 
 *-------------------------------------------------------------
 */



 function renderWaveSurfers() {
    // Cache waveform elements to avoid querying the DOM repeatedly
    const waveformElements = document.querySelectorAll(".waveform");
  
    for (let i = 0; i < waveformElements.length; i++) {
      const waveformElement = waveformElements[i];
      const playerId = waveformElement.getAttribute('data-audio-id');
      
      // Avoid repeated DOM querying by caching the result
      const playButton = document.querySelector(`.btn-toggle-play[data-player-id="${playerId}"]`);
      
      // Ensure that WaveSurfer is only initialized if not already initialized
      if (typeof WaveSurfer !== 'undefined' && waveformElement && !waveformElement.dataset.initialized) {
        // Mark this element as initialized to avoid reinitialization
        waveformElement.dataset.initialized = 'true';
        
        // Initialize WaveSurfer for this waveform
        initilaizeWavesurfer(waveformElement, waveformElement.getAttribute('data-audio-url'), playButton, playerId);
      }
    }
  }
  

