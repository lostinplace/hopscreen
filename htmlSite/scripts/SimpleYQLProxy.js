
//handles content received for google movies requests
function googleMoviesFrameLoaded() {
  debugger;
}

$(function () {
  //the yql base url for proxies
  var yqlURL = 'http://query.yahooapis.com/v1/public/yql';

  //store the results of our google movies query
  var googleMoviesResultsHTML;

  //handle the click event for "submit" button
  $('#locationForm').on('click', '#submitButton', function (e) {
    //setup our yql query
    var query = "select * from html where url='http://www.google.com/movies?near={0}'";

    
    //zip=near
    query = query.replace('{0}', $('#zipInput').val());
    $('#queryInput').val(url);
    $.ajax({
      url:'http://query.yahooapis.com/v1/public/yql',
      data: 'q='+query,
      success:function(a,b,c){
        //store the results of our google movies query
        googleMoviesResultsHTML = $(a).find('results:first>body');
      },
      onerror:function(a,b,c){
        alert('ERROR');
      }
    });
    
  });
});
