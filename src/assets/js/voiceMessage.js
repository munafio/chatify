import audioRecorder from './audioRecorder'
import WaveSurfer from 'wavesurfer.js';
import RecordPlugin from 'wavesurfer.js/dist/plugins/record.js';

let recordBtn = document.querySelector('.record-button')
let cancelBtn = document.querySelector('.cancel-record')
let sendBtn = document.querySelector('.send-button')
let fileBtn = document.querySelector('.file-button')
let messageInput = document.querySelector(".m-send")
let recording = document.getElementById('recording')
let isRecording = false;
let wavesurfer, record
let scrollingWaveform = false

recordBtn.addEventListener('click', event => {
  let btn = null;
  if (event.target.tagName == 'SVG') {
    btn = event.target
  } else if (event.target.tagName == 'BUTTON') {
    btn = event.target.querySelector('svg')
  } else {
    btn = event.target.closest('svg')
  }
  console.log(event.target.tagName);
  event.preventDefault();

  audioRecorder.link();

  if (wavesurfer) {
    wavesurfer.destroy()
  }
  wavesurfer = WaveSurfer.create({
    container: '#recording',
    waveColor: 'rgb(200, 0, 200)',
    progressColor: 'rgb(100, 0, 100)',
    height: 40,
    barWidth: 4,
    barGap: 5,
    barRadius: 5,
  })

  // Initialize the Record plugin
  record = wavesurfer.registerPlugin(RecordPlugin.create({ scrollingWaveform, renderRecordedAudio: false }))

  if (!isRecording) {
    btn.classList.remove('fa-microphone')
    btn.classList.add('fa-stop')
    recording.style.display = 'block'
    emojiButton.style.display = 'none'
    messageInput.style.display = 'none'
    sendBtn.style.display = 'none'
    fileBtn.style.display = 'none'
    cancelBtn.style.display = 'block'

    isRecording = true
    audioRecorder.start().then(() => {
      record.startRecording();
    }).catch(error => {
      if (error.message.includes("mediaDevices API or getUserMedia method is not supported in this browser.")) {
        console.log("To record audio, use browsers like Chrome and Firefox.");
      }
    })
  } else {
    btn.classList.remove('fa-stop')
    btn.classList.add('fa-microphone')
    recording.style.display = 'none'
    emojiButton.style.display = 'block'
    messageInput.style.display = 'block'
    sendBtn.style.display = 'block'
    fileBtn.style.display = 'block'
    cancelBtn.style.display = 'none'

    audioRecorder.stop().then(audioBlob => {
      record.pauseRecording()

      isRecording = false;
      console.log(audioBlob);
      audioMessage = audioBlob
    }).catch(error => {
      switch (error.name) {
        case 'InvalidStateError':
          console.log("An InvalidStateError has occured.");
          break;
        default:
          console.log("An error occured with the error name " + error.name);
      }
    })
  }
})
cancelBtn.addEventListener('click', event => {
  event.preventDefault()
  audioRecorder.cancel()
  record.stopRecording();

  let btn = document.querySelector('svg')
  btn.classList.remove('fa-stop')
  btn.classList.add('fa-microphone')
  recording.style.display = 'none'
  emojiButton.style.display = 'block'
  messageInput.style.display = 'block'
  sendBtn.style.display = 'block'
  fileBtn.style.display = 'block'
  cancelBtn.style.display = 'none'
})
