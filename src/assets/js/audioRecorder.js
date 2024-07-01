import { MediaRecorder, register } from 'extendable-media-recorder';
import { connect } from 'extendable-media-recorder-wav-encoder';

var audioRecorder = {
  audioBlob: [],
  mediaRecorder: null,
  streamBeingCaptured: null,

  link: async function () {
    await register(await connect())
  },

  start: function () {
    if (!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
      return Promise.reject(new Error('mediaDevices API or getUserMedia method is not supported in this browser.'));
    } else {
      return navigator.mediaDevices.getUserMedia({
        audio: true
      })
        .then(stream => {
          audioRecorder.streamBeingCaptured = stream;
          audioRecorder.mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/wav' });
          audioRecorder.audioBlob = [];
          audioRecorder.mediaRecorder.addEventListener('dataavailable', event => {
            audioRecorder.audioBlob.push(event.data);
          })
          audioRecorder.mediaRecorder.start()
        })
    }
  },
  stop: function () {
    return new Promise(resolve => {
      let mimeType = audioRecorder.mediaRecorder.mimeType;

      audioRecorder.mediaRecorder.addEventListener('stop', () => {
        let audioBlob = new Blob(audioRecorder.audioBlob, { mimeType: mimeType });

        resolve(audioBlob)
      })

      audioRecorder.mediaRecorder.stop();
      audioRecorder.stopStream();
      audioRecorder.resetRecordingProperties();
    })

  },
  stopStream: function () {
    audioRecorder.streamBeingCaptured.getTracks()
      .forEach(track => {
        track.stop();
      });
  },
  resetRecordingProperties: function () {
    audioRecorder.mediaRecorder = null;
    audioRecorder.streamBeingCaptured = null;
  },
  cancel: function () {
    audioRecorder.mediaRecorder.stop()
    audioRecorder.stopStream()
    audioRecorder.resetRecordingProperties()
  }
}
export default audioRecorder;

