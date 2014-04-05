/*!
 * SAM Connect
 * http://www.samdesk.io
 * V1.0
 *
 * Copyright 2013, Social Asset Management Ltd.
 *
 * Date: 28/08/2013
 */
 
// Declare globals
var samCurrentStory = '';
var samLoadingStory = false; // Blocking

// Checkboxes for Admin->settings
(function () {
var a = function (b) {
	return Array.prototype.slice.call(document.querySelectorAll(b))
};
document.addEventListener("DOMContentLoaded", function () {
	var b;
	a(".sgFieldCB").forEach(function (c) {
		if (c.className === ("sgFieldCB on")) {
			c.lastElementChild.checked = true
		}
		c.addEventListener("click", function d() {
			if (c.className === "sgFieldCB on") {
				c.className = "sgFieldCB off";
				var e = "off"
			} else {
				c.className = ("sgFieldCB on");
				var e = "on"
			}
			b = c.lastElementChild;
			b.checked = !b.checked;               
		}, false)
	})
}, false)
})();

// declare wExists func
jQuery(document).ready(function (b) { jQuery.fn.wExists=function(){return this.length>0} });

//#==============================================================
//# Story Picker In Post/Page Edit screen
//#==============================================================
function ________SAM_storyPicker(){}

// Init
function initialiseStoryPicker(){
	
	// This is additional to normal thickbox loading
	jQuery('#SAMStoryPicker').click(function(){
				
		// Retrieve Stories
		loadStories();
		
		// Load TB
		tb_show("HAI","#TB_inline?width=800&inlineId=samWrap",null); 
		
		// Wait for render...
		setTimeout(function(){
			
			// Brute force resize..
			resizeTB();			
			
			// Set window resize to also resize :)
			// This is glitchy as WordPress also auto-resizes, will do for v1.0
			jQuery(window).resize(function(){
				
				setTimeout(function(){
					resizeTB();
				},10);
				
			});
			
		},0);
		
	});
	
}

// Brute force resize of TB
function resizeTB(){

	// Brute the dimensions
	jQuery('#TB_window').css({
		
		width: 			'90%',
		height: 		'90%',
		left:			'auto',
		'margin-left': 	'5%'
		
	});
	
	// Hide Title
	jQuery('#TB_title').hide();
	
	// Resize our stuff
	var w = jQuery('#TB_window').width(); var h = jQuery('#TB_window').height();
	jQuery('#samStoryContent').css('width',(w-241) + 'px').css('height',h);
	jQuery('#samStoryContent').css('height',h-1);
	
	// Resize depending if select bar out!
	if (jQuery('#samAssetSelectBar').css('display') == "none")
		jQuery('#samStoryContent #samStoryAssetWrap').css('height',(h-20-22)+'px'); //4040
	else
		jQuery('#samStoryContent #samStoryAssetWrap').css('height',(h-94-22)+'px'); //4040
	var loadingHalf = ((h/2)-50);
	jQuery('#samLoading').css('height',(h-loadingHalf) + 'px').css('padding-top',loadingHalf + 'px');
			
}

// Loads a current story based on globvar window.samCurrentStory (assumes legit story id)
function selectCurrentStory(){

	if (window.samCurrentStory){
		
		// Assign Focus	
		jQuery('#samStoryList .story')
			.removeClass('selected')
			.filter('[data-storyid="' + window.samCurrentStory + '"]')
			.addClass('selected');
			
		// Load story
		loadStory(window.samCurrentStory);
	}
	
}

// Load Stories from AJAX (which retrieves them via PHP Curl from API)
function loadStories(){
	
	// Ensure TB sized correctly
	resizeTB();

	// Empty these
	jQuery('#samStoryList').html('').hide();
	jQuery('#samStoryContent').html('').hide();
		
	// Show Loading dialog
	jQuery('#samLoading').slideDown(300);

	// Retrieve Stories from API
    SAMConnectAdmin_postToAEP({
        t: "stories"
    }, function (b) {

		// Parse JSON
		var a = jQuery.parseJSON(b);		
		
		// Tiny validation
		if (typeof a == "object"){

			// For now don't discern amongst stories (no user known)
			// Gross brutal output
			jQuery('#samStoryList').append('<div class="storyGroupHead">Stories</div>');
			jQuery('#samStoryContent').append('<div class="colHead"></div><div id="storyResultsLoading" style="display: none;"><img src="' + SAMCU + 'i/cog2g_40.gif" alt=""></div>');
			jQuery('#samStoryContent').append('<div id="samAssetSelectBar"><div id="samAssetSelected"></div><div id="samAssetInsert"><button id="samInsertAsset" type="button" class="button button-primary button-large">Insert Into Post</button></div></div>');
			jQuery('#samStoryContent').append('<div id="samStoryAssetWrap"></div><div id="assetLoadingStoryAssets"></div>');			
			
			// Cycle through objects creating GUI
			jQuery.each(a,function(ind,ele){
				// Gross get of id from xml guid prop
				var storyID = ele.id;
				
				// Select the first cycled story to load assets for
				if (storyID) {

					// Set Focus if not already selected
					if (!window.samCurrentStory) window.samCurrentStory = storyID;				
					
				}
			
				// Create header				
				// Taken by force from html template 21/08/2013
				var storyHTML = '<div class="story liveStory';
				if (storyID == window.samCurrentStory) storyHTML += ' selected';
				storyHTML += '" id="story' + storyID + '" data-storyid="' + storyID + '">';
				storyHTML += '				<div class="title">' + ele.name + '</div>';
				storyHTML += '				<div class="contenTypeStats">';
				storyHTML += '					<div class="textNo fourCharNo selected">' + fourCharNo(ele.socialAssets.length) + ' assets</div>';
				storyHTML += '				</div>';
				storyHTML += '			 </div>';

				// Inject				
				jQuery('#samStoryList').append(storyHTML);
			});
			
			setTimeout(function(){				
				
				// Select the story! (also starts retrieving)
				selectCurrentStory();
				
			},0);

			// Show
			jQuery('#samStoryList').show();
			jQuery('#samStoryContent').show();

			// Bind clicks/kill loading after render
			setTimeout(function(){
				
				// Bind Clicks
				bindStoryClicks();
				
				// Hide Loading
				jQuery('#samLoading').slideUp(300);
				
			},0);
			
		}
		
    })
	
}

// Loads a singular stories assets into port
function loadStory(storyID){

	// Brutal Blocking
	if (!window.samLoadingStory){
	
		// Block
		window.samLoadingStory = true;
		
		// Show Loading
		jQuery('#samStoryContent #storyResultsLoading').fadeIn(100);
		
		// Declare wrapper
		var wrapSelector = '#samStoryContent #samStoryAssetWrap #samStoryResults';
		
		// See if wrap exists... if not create
		if (!jQuery(wrapSelector).wExists()) jQuery('#samStoryAssetWrap').append('<div id="samStoryResults" class="samStoryResultWrap"></div>');
			
		// varise
		var wrap = jQuery(wrapSelector);
		
		// Empty it, show it
		wrap.html('').show(0);
		
		// Retrieve Story Assets from API via XML WP AJAX
		SAMConnectAdmin_postToAEP({
			t: "story",
			sid: storyID
		}, function (b) {
			
			// Parse JSON
			var a = jQuery.parseJSON(b);
			
			// Tiny validation
			if (typeof a == "object"){
				
				// Secondary Tiny Validation
				if (typeof a.socialAssets == "object"){
							
					// Cycle through objects creating GUI
					jQuery.each(a.socialAssets,function(ind,asset){
									
							// Build + Append to wrap
							wrap.append(buildAssetHTML(asset));
							
					});
			
					setTimeout(function(){
						
						// Bind
						bindAssetClicks();
						
						// Wait for render+150 then rebuild isotope
						setTimeout(function(){						
						
							// Kill Isotope (if initialized)
							if (jQuery(wrapSelector).hasClass('isotope')) {
								jQuery(wrapSelector).isotope('destroy');
							}
							
							// Rebuild Isotope
							setupIsotope(wrapSelector,'.assetFull');
	
						},150);
						
						
					},0);
					
				}
				
			}
			
			// Either Way after render do these...
			setTimeout(function(){
				
				// Bind Clicks
				bindStoryClicks();
				
				// Recreates heights
				resizeTB();
				
				// Hide Loading
				jQuery('#samStoryContent #storyResultsLoading').fadeOut(100);
			
			},0);
			
			// And after 500 checktoresize
			setTimeout(function(){
				
				// Check sizes match				
				checkToResize(wrapSelector);
								
			},500);
	
			
		});
		
		// Unblock
		window.samLoadingStory = false;
		
	}
	
}

// Checks size of port matches what it should and adjusts
// calls back to itself so can loop...
function checkToResize(wrapSelector){
	
	// If zero'd
	if (jQuery(wrapSelector).css('height') == '0px'){
		
		// Relayout Isotope
		relayoutIsotope(wrapSelector);
		
		setTimeout(function(){
						
			// Loop!
			if (jQuery(wrapSelector).css('height') == '0px') setTimeout(function(){ checkToResize(wrapSelector); },200);
			
		},0);
	}
	
}

// Adapted from app - simply selects + assigns focus to globvar
function bindStoryClicks(){

		// Bind the click Selecting of a story
        jQuery('#samStoryWrap #samStoryList .story').click(function(){
	
			// Localise ID
			var id = jQuery(this).attr('data-storyid');
	
        	// Discern selecting/deselecting       		
            if (jQuery(this).hasClass('selected')){

            	// Don't allow deselecting
                // jQuery(this).removeClass('selected');

            } else {
				    
				// Log Focus
				window.samCurrentStory = id;				
				
				// Load Story
				selectCurrentStory();
				
            }
			
        });	
			
}

// Bind Clicks on Tweet Objs
function bindAssetClicks(){
	
	// Bind to tweetFull class
	jQuery('.assetFull').click(function(){

		// Localise ID
		var id = jQuery(this).attr('data-storyid');

		// Discern selecting/deselecting
		if (jQuery(this).hasClass('selected')){

			// Deselecting
			jQuery(this).removeClass('selected');
			jQuery('#samAssetSelectBar').hide(0);
			jQuery('#samAssetSelectBar #samAssetSelected').html('');

		} else {
				
			// Deselect class
			jQuery('#samStoryAssetWrap .assetFull').not(this).removeClass('selected');
			
			// Select this
			jQuery(this).addClass('selected');			
			
			// Build Preview
			var selectedAssetHTML  = '<div id="samSelectedActions">1 Selected<br /><button id="samClearSelect" type="button" class="button">Clear</button></div>';
			
				// Got Img?
				if (jQuery('.assetMedia img',this).wExists()) selectedAssetHTML += '<img src="' + jQuery('.assetMedia img',this).attr('src') + '" alt="Selected Tweet" />';
			
			// Inject Preview
			jQuery('#samAssetSelectBar #samAssetSelected').html(selectedAssetHTML);

			// Inject ID's etc. into go button, note use of attr('title') for possibly minified str's
			var embedId = jQuery(this).attr('data-embedid');
			var socialType = jQuery(this).attr('data-socialtype');
			jQuery('#samAssetSelectBar #samAssetInsert #samInsertAsset').attr({
				'data-embedid': embedId,
				'data-socialtype': socialType
			});
			
			// Assign clear button and Insert Button
			setTimeout(function(){
				
				// Bind Clear button
				jQuery('#samAssetSelectBar #samClearSelect').unbind('click').click(function(){
					
					// Deselect all
					jQuery('#samStoryAssetWrap .assetFull').removeClass('selected');
					jQuery('#samAssetSelectBar').hide(0);
					jQuery('#samAssetSelectBar #samAssetSelected').html('');
					
					// Clear attr
					jQuery('#samAssetSelectBar #samAssetInsert #samInsertAsset').removeAttr('data-embedid').removeAttr('data-socialtype');
					
				});
				
				// Bind Insert asset button
				jQuery('#samAssetSelectBar #samInsertAsset').unbind('click').click(function(){
					
						// Verify quickly

						var embedId = jQuery(this).attr('data-embedid');
						var socialType = jQuery(this).attr('data-socialtype');
					
						// Direct to the point!
						if (embedId && socialType){
							
							// Actual injector
							sendToEditorIncRender('[SAMASSET embedid="' + embedId + '" socialtype="' + socialType + '"]');
					
						}
				});
				
			},0);
			
			// Show Select Bar
			jQuery('#samAssetSelectBar').show(0);
		
		}
		
	});
	
} 

// Build Asset HTML 
function buildAssetHTML(asset){

	// Relay :/
	var said = asset.id;
	var avatar = asset.authorAvatarUrl;
	var publicID = asset.publicId;
	var unixTime = asset.postedDate;
	var socialName = asset.authorName;
	var socialType = asset.socialType;
	var handle = asset.authorDisplayName;
	var assetTxt = asset.longText;
	var assetPrettyDate = unix2PrettyDate(asset.postedDate);
    var unsupported = asset.socialType === 'facebook' ? ' unsupported' : '';
	var retHTML = '';

	var embedID = publicID;

	// instagram's embedID is not the same as it's publicID
	if (socialType == 'instagram') {
		embedID = /^.*:\/\/.*instagram\.com\/p\/([^?#\/]+)/.exec(asset.permalink)[1];
	}

	if (asset.media && asset.media.length) {

		if (asset.media[0].mediaType == 'image') {

			// Hacked from template #assetImgTemplateFull	
			retHTML = '<div class="assetFull clickable assetImgFull' + unsupported + '" data-socialtype="' + socialType + '" data-embedid="' + embedID + '">';
			retHTML += '	<div class="asset storyAsset">';
			retHTML += '		<img src="' + avatar + '" alt="">';
			retHTML += '		<div class="assetDets">';
			retHTML += '			<div class="asseter"><span class="asseter" title="' + socialName + '">' + limitStrLen(socialName,14) + '</span>';
			retHTML += ' <span class="asseterHandle" title="' + handle + '">@' + limitStrLen(handle,13) + '</span></div>';
			retHTML += '		</div>';
			retHTML += '		<div class="assetText">' + htmlEncode(assetTxt) + '</div>';
			retHTML += '		<div class="clr"></div>';
			retHTML += '		<div class="assetMeta">';
			retHTML += '			<div class="assetAt">' + assetPrettyDate + '</div>';
			retHTML += '		</div>';
			retHTML += '		<div class="clr"></div>';
			retHTML += '	</div>';
			retHTML += '	<div class="assetMedia"><div class="media" style="background-image:url(' + asset.media[0].mediaUrl + ')" alt="" /></div>';
			retHTML += '</div>';

		} else if (asset.media[0].mediaType == 'video') {

			// Build video code
			var videoCode = makeVideoEmbedCode(asset.media[0].socialType,asset.media[0].mediaUrl);
	
			// Hacked from template #assetVideoTemplateFull	
			retHTML = '<div class="assetFull clickable assetVidFull' + unsupported + '" data-socialtype="' + socialType + '" data-embedid="' + embedID + '">';
			retHTML += '	<div class="asset storyAsset">';
			retHTML += '		<img src="' + avatar + '" alt="">';
			retHTML += '		<div class="assetDets">';
			retHTML += '			<div class="asseter"><span class="asseter" title="' + socialName + '">' + limitStrLen(socialName,14) + '</span>';
			retHTML += ' <span class="asseterHandle" title="' + handle + '">@' + limitStrLen(handle,13) + '</span></div>';
			retHTML += '		</div>';
			retHTML += '		<div class="assetText">' + htmlEncode(assetTxt) + '</div>';
			retHTML += '		<div class="clr"></div>';
			retHTML += '		<div class="assetMeta">';
			retHTML += '			<div class="assetAt">' + assetPrettyDate + '</div>';
			retHTML += '		</div>';
			retHTML += '		<div class="clr"></div>';
			retHTML += '	</div>';
			retHTML += '	<div class="assetMedia">' + videoCode + '</div>';
			retHTML += '</div>';

		}

	} else {

		// Hacked from template #assetTemplateFull	
		retHTML = '<div class="assetFull clickable' + unsupported + '" data-socialtype="' + socialType + '" data-embedid="' + embedID + '">';
		retHTML += '	<div class="asset storyAsset">';
		retHTML += '		<img src="' + avatar + '" alt="">';
		retHTML += '		<div class="assetDets">';
		retHTML += '			<div class="asseter"><span class="asseter" title="' + socialName + '">' + limitStrLen(socialName,14) + '</span>';
		retHTML += ' <span class="asseterHandle" title="' + handle + '">@' + limitStrLen(handle,13) + '</span></div>';
		retHTML += '		</div>';
		retHTML += '		<div class="assetText">' + htmlEncode(assetTxt) + '</div>';
		retHTML += '		<div class="clr"></div>';
		retHTML += '		<div class="assetMeta">';
		retHTML += '			<div class="assetAt">' + assetPrettyDate + '</div>';
		retHTML += '		</div>';
		retHTML += '		<div class="clr"></div>';
		retHTML += '	</div>';
		retHTML += '</div>';

	}
	
	return retHTML;
}

// WP AJAX Access Wrapper
function SAMConnectAdmin_postToAEP(a, b) {
    var c = {
        action: "SAMConnect",
        SAMRequestVar: a
    };
    jQuery.post(ajaxurl, c, function (d) {
        if (typeof b == "function") {
            b(d)
        }
        return false
    })
}

// Send's code into editor and causes re-render (for mce plugin generated view visual aids)
function sendToEditorIncRender(html){
	
	// To force fire "onBeforeSetContent" I've basically made this retrieve send to editor then retrieve and re-set!
	window.send_to_editor(html);
	var c = tinyMCE.activeEditor.getContent();
	tinyMCE.activeEditor.setContent(c);
	
	// Here you could cycle through each image in editor and use its data-attributes to super impose 
	// Tweeter + tweet Str :), later!
	/*setTimeout(function(){
		
		jQuery('.samAssetVisAid').each(function(ind,ele){
			
			
		});
		
	},0);*/

}
				
function ________SAM_General_Lib(){}	

// Returns a pretty ver of big numbers, no more than 4 chars long
function fourCharNo(no){

	if (no < 1000) return no;
	if (no > 999 && no < 1000000) return (( no / 1000 ).toFixed(1)).toString().replace('.0','') + 'K';
	if (no >= 1000000) return (( no / 1000000 ).toFixed(1)).toString().replace('.0','') + 'M';

	return no;
	
}

// Limits length of a string
function limitStrLen(s,l){

	if (s.length <= l) return s; else {

		return s.substr(0,l) + '..';

	}

}

// Encodes HTML string (modern jQuery)
function htmlEncode(x){
  return jQuery('<div/>').text(x).html();
}

// Prepend's Zero's
function prependZeros(no){
	if (String(no).length < 2) return '0'+String(no);
	return no;
}

// Converts a unix time stamp to a pretty date str
function unix2PrettyDate(u){

	var a = new Date(u*1000);
	var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
	return months[a.getMonth()] + ' ' + a.getDate() + ', ' + a.getFullYear() + ' ' + prependZeros(a.getHours()) + ':' + prependZeros(a.getMinutes());

}

// Adapted from func in runtime.js
// Makes video embed code
function makeVideoEmbedCode(vService,vCode){

	// Switch Services
	switch (vService){

		case "instagram":

			return '<video class="video" controls=""><source src="' + vCode + '" type="video/mp4"></video>' 
			break;

		// YOUTUBE
		// http://stackoverflow.com/questions/2068344/how-to-get-thumbnail-of-youtube-video-link-using-youtube-api
		case "youtube":
		
			return '<iframe width="294" height="165" src="' + vCode + '?rel=0&wmode=opaque" frameborder="0" allowfullscreen></iframe>';
			break;

		// Vimeo
		// http://stackoverflow.com/questions/1361149/get-img-thumbnails-from-vimeo
		case "vimeo":

			return '<iframe src="//player.vimeo.com/video/' + vCode + '?portrait=0&amp;badge=0" width="294" height="165" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
			break;
	}

}


function ________SAM_Isotope(){}

// Init IsoTope
function setupIsotope(wrapSelector,itemSelectorStr){
	
	// After rendered
	setTimeout(function(){

		// Hard size images...
		jQuery('img',wrapSelector).each(function(ind,ele){
			if (jQuery(ele).width() > 0) jQuery(ele).css('width',jQuery(ele).width() + 'px');
			if (jQuery(ele).height() > 0) jQuery(ele).css('height',jQuery(ele).height() + 'px');
		});

		jQuery(wrapSelector).isotope({
		    itemSelector : itemSelectorStr
		});

	},0);

}


// Re layout Isotope
function relayoutIsotope(wrapSelector){

	// Only fire if items present!
	if (jQuery('.noAssetsDialog',wrapSelector).wExists()){

			jQuery(wrapSelector).css('height','300px');

	} else {

		jQuery(wrapSelector).isotope( 'reLayout' );

	}

}