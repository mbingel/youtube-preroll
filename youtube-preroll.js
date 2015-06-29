console.log(youtube_preroll_options);

var youtube_preroll_seenPreroll=false;
var youtube_preroll_playingId='';
var youtube_preroll_player;
function onYouTubeIframeAPIReady() {
  youtube_preroll_player = new YT.Player('youtube_preroll_player', {
    height: '390',
    width: '640',
    videoId: youtube_preroll_options.mainroll,
    events: {
      'onStateChange': function(event) {
        if (event.data==1&&youtube_preroll_seenPreroll==false) {
          youtube_preroll_player.pauseVideo();
          youtube_preroll_player.loadVideoById(youtube_preroll_options.preroll);
          youtube_preroll_playingId=youtube_preroll_options.preroll;
          youtube_preroll_seenPreroll=true;
          youtube_preroll_player.playVideo();
        } else if (event.data==0&&youtube_preroll_playingId==youtube_preroll_options.preroll) {
          youtube_preroll_player.loadVideoById(youtube_preroll_options.mainroll);
          youtube_preroll_playingId=youtube_preroll_options.mainroll;
          youtube_preroll_player.playVideo();
        }
      }
    }
  });

}
console.log('YoutubePreroll() - preparation done');
