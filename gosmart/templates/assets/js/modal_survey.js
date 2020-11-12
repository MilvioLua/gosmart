;(function ( $, window, document, undefined ) {
	/** Create the defaults once **/
	var pluginName = "modalsurvey",
			defaults = {
			survey_options 	: [],
			unique_key		: ''
	};
/** The actual plugin constructor **/
function Plugin ( element, options ) {
	this.element = element;
	this.settings = $.extend( {}, defaults, options );
	this._defaults = defaults;
	this._name = pluginName;
	this.init();
}

/** Avoid Plugin.prototype conflicts **/
$.extend( Plugin.prototype, {
		init: function () {
		var ua = window.navigator.userAgent;
		var iOS = !!ua.match(/iPad/i) || !!ua.match(/iPhone/i);
		var webkit = !!ua.match(/WebKit/i);
		var iOSSafari = iOS && webkit && !ua.match(/CriOS/i);
		var survey = [], unique_key = this.settings.unique_key, survey_options = [], played_question = -1, opentext = [], display = "", rmdni = false, lastScrollTop = 0, played = 0, sanswers = [], msparams = [], timer, pos, survey_aoptions = [], survey_qoptions = [], surveycatscore = new Array(), surveyscore = 0, surveycorrect = 0, chtitle, thistext = '', question_score = new Array(), question_correct = new Array(), question_name = new Array(), question_choice = new Array(),chartstyleparams, chartparams = [], chartelems = {}, random, charttype, chartmode, socialbuttons, socialstyle = "", socialsize = "", survey_participant_form = "", pformstep, endstep, sendbutton, senderror = false, inputtemp, noclose = "false", between = [], soctitle = "", socdesc = "", socimg = "", rating_selected = [], alldisplay_survey_content = "", currenthover, imgwidth, imgheight, imgstyle, answer_status, sba = false, fieldname, thisdata, thisval, customdatas = {}, msanswerclass, customfieldsarray, chstatusexp, chstatus, msvoted = false, played_section = 1, bulktimer = 0, condapplied = 0, admin_comment = '', admin_comment_container = '', boxed = [], selectoptions = [], editmode = "false", themeprefix = "", pform = false;
		var protocol = ('https:' == window.location.protocol ? 'https://' : 'http://');
		var msuseractionform = document.getElementById( "ms-user-action-form" );
		if ( document.getElementById( "send_ms_ua_form_link" ) != null ) {
			document.getElementById( "send_ms_ua_form_link" ).addEventListener( "click", function () {
			  msuseractionform.submit();
			});
		}
		if ( this.settings.survey_options != undefined ) {
			survey = JSON.parse( this.settings.survey_options );
		}
		if ( survey.options != undefined ) {
			survey_options = JSON.parse( survey.options );
		}
		if ( parseInt( survey.current_score ) > 0 && parseInt( surveyscore ) == 0 ) {
			surveyscore = survey.current_score;
		}
		survey.confirmed = "false";
		//if ( survey.social != undefined ) survey_social = JSON.parse( survey.social );
		if ( survey.ao != undefined ) {
			survey_aoptions = survey.ao;
		}
		if ( survey.qo != undefined ) {
			survey_qoptions = survey.qo;
		}
		survey.customfields = survey_options[ 159 ];
		if ( survey.align == undefined || survey.align == "" ) {
			survey.align = "left";
		}
		if ( survey.visible == undefined || survey.visible == "" ) {
			survey.visible = "false";
		}
		if ( survey.width == undefined ) {
			survey.width = "100%";
		}
		if ( survey.textalign == undefined ) {
			survey.textalign = "";
		}
		if ( survey.removesame == undefined ) {
			survey.removesame = "";
		}
		survey.id = survey.survey_id;
		survey.last_answer = "";
		if ( survey.display == undefined ) {
			survey.display = "";
		}
		if ( survey_options[ 156 ] >= 0 ) {
			survey.quiztimer = survey_options[ 156 ] / 1000;
		}
		else {
			survey.quiztimer = 0;
		}
		survey.quiztimertype = survey_options[ 157 ];
		survey.qtcurrent = survey.quiztimer;
		if ( survey.display_once != '' && survey.display_once != "undefined" ) {
				if ( survey_options[ 143 ] == '' ) {
					survey_options[ 143 ] = 99999;
				}
				if ( survey_options[ 143 ] > 0 ) {
					msparams = [ 'modal_survey', survey.display_once, parseFloat( survey_options[ 143 ] ) * 60, 'minutes' ];
					setCookie( msparams );
				}
		}
		if ( survey_options[ 153 ] != "" ) {
			survey.rating_icon = survey_options[ 153 ];
		}
		else {
			survey.rating_icon = "star";
		}
		if ( survey_options[ 21 ] != "" ) {
			survey.survey_conds = jQuery( survey_options[ 21 ] ).toArray();
		}
		else {
			survey.survey_conds = "";
		}
		if ( survey_options[ 23 ] == undefined ) {
			survey_options[ 23 ] = 5000;
		}
		if ( survey_options[ 133 ] == undefined || survey_options[ 133 ] == "" ) {
			survey_options[ 133 ] = "preloader";
		}
		if ( survey_options[ 134 ] == undefined || survey_options[ 134 ] == "" ) {
			survey_options[ 134 ] = "";
		}
		if ( survey_options[ 135 ] == undefined || survey_options[ 135 ] == "" ) {
			survey_options[ 135 ] = 1000;
		}
		if ( survey_options[ 145 ] == undefined || survey_options[ 145 ] == "" ) {
			survey_options[ 145 ] = "remove";
		}
		if ( survey.social[ 0 ] == 'on' ) {
			if ( survey.social[ 2 ].indexOf( 'large' ) >= 0 ) {
				socialsize = " is-large";
			}
			if ( survey.social[ 2 ].indexOf( 'clean' ) >= 0  ) {
				socialstyle = " is-clean";
			}
			socialbuttons = '<div class="social-sharing' + socialstyle + socialsize + '">';
				jQuery.map( survey.social[ 1 ], function( val, i ) {
					if ( val == "facebook" ) {
						socialbuttons += '<a target="_blank" href="http://www.facebook.com/sharer.php?u=' + survey.social[ 4 ] + '" class="share-facebook ms-social-share"><span class="icon icon-facebook" aria-hidden="true"></span><span class="share-title">' + survey.languages.share + '</span></a>';
					}
					if ( val == "twitter" ) {
						socialbuttons += '<a target="_blank" href="http://twitter.com/share?url=' + survey.social[ 4 ] + '&amp;text=' + survey.social[ 6 ] + '" class="share-twitter ms-social-share"><span class="icon icon-twitter" aria-hidden="true"></span><span class="share-title">' + survey.languages.tweet + '</span></a>';
					}
					if ( val == "pinterest" ) {
						socialbuttons += '<a target="_blank" href="http://pinterest.com/pin/create/button/?url=' + survey.social[ 4 ] + '&amp;media=' + survey.social[ 5 ] + '&amp;description=' + survey.social[ 6 ] + '" class="share-pinterest ms-social-share"><span class="icon icon-pinterest" aria-hidden="true"></span><span class="share-title">' + survey.languages.pinit + '</span></a>';
					}
					if ( val == "googleplus" ) {
						socialbuttons += '<a target="_blank" href="http://plus.google.com/share?url=' + survey.social[ 4 ] + '" class="share-google ms-social-share"><span class="icon icon-google" aria-hidden="true"></span><span class="share-title">' + survey.languages.plusone + '</span></a>';
					}
					if ( val == "linkedin" ) {
						socialbuttons += '<a target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&url=' + survey.social[ 4 ] + '&title=' + survey.social[ 6 ] + '&summary=' + survey.social[ 7 ] + '" class="share-linkedin ms-social-share"><span class="icon icon-linkedin" aria-hidden="true"></span><span class="share-title">' + survey.languages.share + '</span></a>';
					}
				});
			socialbuttons += '</div>';
		}
		else {
			socialbuttons = "";
		}
		var modal_survey = jQuery( "#survey-" + survey.survey_id + "-" + unique_key );
		//bbPress Extension
		if ( jQuery( "#bbpress-forums" ).length > 0 && survey_options[ 17 ] == "embed_topics" ) {
			modal_survey.css( "display", "none" );
			modal_survey.prependTo( "#bbpress-forums" );
			modal_survey.addClass( "bbpress-modal-survey" );
			if ( survey_options[ 158 ] == "fade" ) {
				modal_survey.fadeIn( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {} );
			}
			else {
				modal_survey.slideDown( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {} );
			}
		}

		
		if ( survey_options[ 171 ] != undefined && survey_options[ 171 ].indexOf( 'theme' ) >= 0 ) {
			modal_survey.addClass( "modal-survey-theme" );
			modal_survey.addClass( "modal-survey-" + survey_options[ 171 ] );
			themeprefix = survey_options[ 171 ] + "-";
		}
		else {
			themeprefix = "";
		}

		if ( survey.style != 'flat' ) {
			survey.num_width = survey.width.replace("%","");
			survey.leftmargin = "0";
			if ( survey.align == "center" && survey.num_width < 100 ) {
				survey.leftmargin = ( 100 - survey.num_width ) / 2;
			}
			modal_survey.attr( "style", "position: fixed;" + survey.align + ":0px;z-index: 999999;width:" + survey.width + ";margin-left: " + survey.leftmargin + "%;bottom:-300px;" ); 
			modal_survey.prependTo("body");
			modal_survey.removeClass("modal-survey-embed");
		}
		  
		jQuery("body").on( "click", "." + survey.survey_id + ", .ms" + survey.survey_id, function( e ) {
			e.preventDefault();
			played_question = 0;
			if ( parseInt( survey_options[13] ) == 1 ) jQuery( "#bglock" ).fadeIn( 1000 );
			modal_survey.css( "z-index", "999999" );
			play_survey();
		})
		if ( parseInt( survey_options[ 13 ] ) == 1 && survey.style != 'flat' ) {
			if ( jQuery( "#bglock" ).length == 0 ) jQuery( "body" ).prepend( "<div id='bglock'></div>" );
			if ( ( survey.expired != 'true' ) && ( survey.style != 'click' ) && ( survey_options[ 15 ] != 1 ) ) {
				setTimeout( function() {
					jQuery( "#bglock" ).fadeIn( 1000 );
				}, parseInt( survey_options[ 135 ] ) );
			}
		}
		if ( survey_options[ 15 ] != 1 && survey.style == 'modal' ) {
			if ( survey.preview == "true" ) {
				setTimeout( function() {
					played_question = 0;
					play_survey();
				}, 0 );
			}
			else {
				setTimeout( function() {
					played_question = 0;
					play_survey();
				}, survey_options[ 135 ] );				
			}
		}
		if ( survey.style == 'flat' ) {
			if ( survey.visible == 'false' ) {
				modal_survey.hide();
			}
		}
		if ( survey.style == 'flat' && survey.visible == 'true' ) {
			played_question = 0;
			play_survey();
		}
		jQuery( window ).bind( 'scroll' , function () {
			if ( modal_survey != undefined ) {
				if ( played == 0 ) {
					if ( survey.style == 'flat' && played_question == -1 && modal_survey.visible() ) {
						played_question = 0;
						play_survey();
					}
					else
					{
						if ( jQuery( window ).scrollTop() + jQuery( window ).height() > jQuery( document ).height() - ( ( jQuery( document ).height() / 100 ) * 10 ) && jQuery( this ).scrollTop() > lastScrollTop && played_question == -1 ) {
							if ( survey.style == 'modal' && survey_options[ 15 ] == 1 ) {
								if ( parseInt( survey_options[ 13 ] ) == 1 ) {
									jQuery( "#bglock" ).fadeIn( 1000 );
								}
								played_question = 0;
								play_survey();
							}
						}
						parallaxScroll();
						clearTimeout( timer );
						if ( played_question >= 0 ) {
							timer = setTimeout( refresh , 150 );
						}
					}
				}
			}
		});
		var refresh = function () {
			if ( played_question >= 0 && ( parseInt( modal_survey.css( "top" ).replace( "px", "" ) ) > 0 && parseInt( modal_survey.css( "bottom" ).replace( "px", "" ) ) > 0 ) ) {
				if ( survey_options[ 0 ] == "bottom" ) {
					modal_survey.animate({ bottom: '10px' }, parseInt( survey_options[ 11 ] ), 'easeOutBounce' );
				}
				if ( survey_options[ 0 ] == "center" ) {
					modal_survey.animate({ top: ( ( jQuery( window ).height() - modal_survey.height() ) / 2 ) + "px" }, parseInt( survey_options[ 11 ] ), 'easeOutBounce' );
				}
				if ( survey_options[ 0 ] == "top" ) {
					modal_survey.animate({ top: '10px' }, parseInt( survey_options[ 11 ] ), 'easeOutBounce' );
				}
			}
		}
		function parallaxScroll() {
			var scrolledY = jQuery( window ).scrollTop();
			if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_element" ).length ) {
				if ( survey_options[ 0 ] == "center" ) {
					pos = "top";
				}
				else {
					pos = survey_options[0];
				}
				if ( ( parseInt( modal_survey.css( "top" ).replace( "px", "" ) ) > 0 && parseInt( modal_survey.css( "bottom" ).replace( "px", "" ) ) > 0 ) ) {
					if ( scrolledY < lastScrollTop ) {
						modal_survey.css( pos, parseInt( modal_survey.css( pos ).replace( "px", "" ) ) - parseInt( scrolledY / 5000 ) + "px" );
					}
					else {
						modal_survey.css( pos, parseInt( modal_survey.css( pos ).replace( "px", "" ) ) + parseInt( scrolledY / 5000 ) + "px" );
					}
				}
			}
			lastScrollTop = jQuery( window ).scrollTop();
		}
			
		function isUrlValid( url ) {
			return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
		}

		function countTo( element, from, to, speed, refreshInterval, afterString, decimals, onUpdate, onComplete) {
		var loops = Math.ceil( parseInt( speed ) / parseInt( refreshInterval ) ),
			element = jQuery( element ),
			increment = ( parseInt( to ) - parseInt( from ) ) / parseInt( loops );
			var loopCount = 0,
				value = from,
				interval = setInterval( updateTimer, parseInt( refreshInterval ) );
			function updateTimer() {
				value += increment;
				loopCount++;
				$( element ).html( value.toFixed( decimals ) + afterString );

				if ( typeof(onUpdate) == 'function' ) {
					onUpdate.call( element, value );
				}

				if ( loopCount >= loops ) {
					clearInterval( interval );
					value = to;

					if ( typeof( onComplete ) == 'function' ) {
						onComplete.call( element, value );
					}
				}
			}
		}

		jQuery( document ).on( 'keyup', '#survey-' + survey.survey_id + '-' + unique_key + ' .open_text_answer', function( e ){
			if ( jQuery( this ).val().length > 1 ) {
				if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).hasClass( "passive" ) ) {
					jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice img" ).attr( "src", survey.plugin_url + "/templates/assets/img/" + themeprefix + "next.png" )
					jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).removeClass( "passive" ).addClass( "active" );
				}
			}
			else {
				if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).hasClass( "active" ) ) {
					jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice img" ).attr( "src", survey.plugin_url + "/templates/assets/img/" + themeprefix + "next-passive.png" )				
					jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).removeClass( "active" ).addClass( "passive" );
				}
			}
			if( e.keyCode == 13 ) {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).trigger( "click" );
			}
		});
			/*jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answers" ).on( 'touchstart', function( e ) {
				var el = jQuery( this );
				if ( iOSSafari == true ) {
					el.click();
				}
			});*/
		
		function send_answer( thiselem, answer ) {
			abortTimer();
			survey.last_answer = answer;
			survey.last_open = "";
			survey.last_question = played_question;
			if ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) {
					return;
			}
			if ( survey_aoptions[ parseInt( played_question + 1 ) + "_" + answer ] != undefined ) {
				admin_comment = survey_aoptions[ parseInt( played_question + 1 ) + "_" + answer ][ 16 ];
			}
			if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice img" ).length > 0 ) {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice img" ).css( "display", "none" );
			}
			if ( survey_options[ 133 ].indexOf( ".gif" ) < 0 ) {
				survey_options[ 133 ] = survey_options[ 133 ] + ".gif";
			}
			if ( survey_options[ 171 ] != undefined && survey_options[ 171 ].indexOf( 'theme' ) >= 0 ) {
				survey_options[ 133 ] = survey_options[ 171 ] + "-preloader.svg";
			}
			if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).length > 0 ) {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).html( '<div id="survey_preloader_multi"><img src="' + survey.plugin_url + '/templates/assets/img/' + survey_options[ 133 ] + '"></div>' );
			}
			else {
				if ( survey_qoptions[ played_question ][ 3 ] == 1 ) {
					thiselem.parent().append( '<div id="survey_preloader"><img src="' + survey.plugin_url + '/templates/assets/img/' + survey_options[ 133 ] + '"></div>' );
				}
				else {
					if ( survey_aoptions[ parseInt( played_question + 1 ) + "_" + answer ] != undefined ) {
						if ( survey_aoptions[ parseInt( played_question + 1 ) + "_" + answer ][ 0 ] == "open" ) {
							thiselem.parent().append( '<div id="survey_preloader"><img src="' + survey.plugin_url + '/templates/assets/img/' + survey_options[ 133 ] + '"></div>' );
						}
						else {
							thiselem.append( '<div id="survey_preloader"><img src="' + survey.plugin_url + '/templates/assets/img/' + survey_options[ 133 ] + '"></div>' );
						}
					}
					else {
						thiselem.append( '<div id="survey_preloader"><img src="' + survey.plugin_url + '/templates/assets/img/' + survey_options[ 133 ] + '"></div>' );
					}
				}
			}
			var thissurvey = [], qa = {}, thisscore = 0, thiscorrect = 0;
			if ( typeof thiselem.attr( "data-qstep" ) != undefined ) {
				var sans = thiselem.attr( "data-qstep" );
			}
			else {
				var sans = "";
			}
				var regExp = /\[(.*?)\]/;
				var matches = regExp.exec( survey.questions[ played_question ][ 0 ] );
				if ( matches != null ) {
					if ( surveycatscore[ matches[ 1 ] ] == undefined && matches[ 1 ] != "-" ) {
						surveycatscore[ matches[ 1 ] ] = 0;
					}
				}
				jQuery.map( survey.questions[ played_question ], function( val, i ) {
					if ( i > 0 ) {
						var matches2 = regExp.exec( val );
						if ( matches2 != null ) {
							if ( surveycatscore[ matches2[ 1 ] ] == undefined && ! jQuery.isNumeric( matches2[ 1 ] ) && matches2[ 1 ] != "-" ) {
								if ( matches2[ 1 ].indexOf( "," ) >= 0 ) {
									var msplitted2 = matches2[ 1 ].split( "," );
									for ( index = 0; index < msplitted2.length; ++index ) {
										if ( msplitted2[ index ] != undefined && ! jQuery.isNumeric( msplitted2[ index ] ) && msplitted2[ index ] != "-" ) {
											if ( surveycatscore[ msplitted2[ index ] ] == undefined ) {
												surveycatscore[ msplitted2[ index ] ] = 0;
											}
										}
									}
								}
								else {
									if ( matches2[ 1 ] != undefined && ! jQuery.isNumeric( matches2[ 1 ] ) && matches2[ 1 ] != "-" ) {
										if ( surveycatscore[ matches2[ 1 ] ] == undefined ) {
											surveycatscore[ matches2[ 1 ] ] = 0;
										}
									}
								}
							}
						}
					}
				})
			if( typeof answer === 'string' ) {
				if ( ! isNaN( parseFloat( survey_aoptions[ parseInt( played_question + 1 ) + "_" + answer ][ 4 ] ) ) ) {
					thisscore = parseFloat( survey_aoptions[ parseInt( played_question + 1 ) + "_" + answer ][ 4 ] );
				}
				if ( parseInt( survey_aoptions[ parseInt( played_question + 1 ) + "_" + answer ][ 5 ] ) >= 0 ) {
					thiscorrect = parseInt( survey_aoptions[ parseInt( played_question + 1 ) + "_" + answer ][ 5 ] );
				}
				if ( matches != null ) {
					if ( matches[ 1 ] != undefined && matches[ 1 ] != "-" ) {
						surveycatscore[ matches[ 1 ] ] = parseFloat( surveycatscore[ matches[ 1 ] ] ) + parseFloat( thisscore );
					}
				}
				var matches3 = regExp.exec( survey.questions[ played_question ][ parseInt( answer ) ] );
				if ( matches3 != null ) {
						if ( matches3 != null ) {
							if ( matches3[ 1 ].indexOf( "," ) >= 0 ) {
								var msplitted = matches3[ 1 ].split( "," );
								for ( index = 0; index < msplitted.length; ++index ) {
									if ( msplitted[ index ].trim() != undefined && ! jQuery.isNumeric( msplitted[ index ].trim() ) && msplitted[ index ].trim() != "-" ) {
										surveycatscore[ msplitted[ index ].trim() ] = parseFloat( surveycatscore[ msplitted[ index ].trim() ] ) + parseFloat( thisscore );
									}
								}
							}
							else {
								if ( matches3[ 1 ] != undefined && ! jQuery.isNumeric( matches3[ 1 ] ) && matches3[ 1 ] != "-" ) {
									surveycatscore[ matches3[ 1 ] ] = parseFloat( surveycatscore[ matches3[ 1 ] ] ) + parseFloat( thisscore );
								}
							}
						}
				}
				surveyscore = parseInt( surveyscore ) + parseInt( thisscore );
				surveycorrect = parseInt( surveycorrect ) + parseInt( thiscorrect );
				question_score[ parseInt( played_question + 1 ) ] = parseInt( thisscore );
				question_correct[ parseInt( played_question + 1 ) ] = parseInt( thiscorrect );
				question_choice[ parseInt( played_question + 1 ) ] = answer;
			}
			else {
				jQuery.map( answer, function( val, i ) {
				if ( jQuery.isNumeric( parseInt( survey_aoptions[ parseInt( played_question + 1 ) + "_" + val ][ 4 ] ) ) ) {
					thisscore = parseFloat( survey_aoptions[ parseInt( played_question + 1 ) + "_" + val ][ 4 ] );
				}
				else {
					thisscore = 0;
				}
				if ( parseInt( survey_aoptions[ parseInt( played_question + 1 ) + "_" + val ][ 5 ] ) >= 0 ) {
					thiscorrect = parseInt( survey_aoptions[ parseInt( played_question + 1 ) + "_" + val ][ 5 ] );
				}
				else {
					thiscorrect = 0;
				}
				if ( survey_aoptions[ parseInt( played_question + 1 ) + "_" + val ][ 0 ] == "numeric" && thisscore == 0 ) {
					thisscore = parseFloat( modal_survey.find( "#survey_answer" + val ).find( ".open_text_answer" ).val() );
					if ( isNaN( thisscore ) ) {
						thisscore = 0;
					}
				}
				if ( matches != null ) {
					if ( matches[ 1 ] != undefined && matches[ 1 ] != "-" ) {
						surveycatscore[ matches[ 1 ] ] = parseFloat( surveycatscore[ matches[ 1 ] ] ) + parseFloat( thisscore );
					}
				}
				var matches3 = regExp.exec( survey.questions[ played_question ][ parseInt( val ) ] );
				if ( matches3 != null ) {
						if ( matches3 != null ) {
							if ( matches3[ 1 ].indexOf( "," ) >= 0 ) {
								var msplitted = matches3[ 1 ].split( "," );
								for ( index = 0; index < msplitted.length; ++index ) {
									if ( msplitted[ index ].trim() != undefined && ! jQuery.isNumeric( msplitted[ index ].trim() ) && msplitted[ index ].trim() != "-" ) {
										surveycatscore[ msplitted[ index ].trim() ] = parseFloat( surveycatscore[ msplitted[ index ].trim() ] ) + parseFloat( thisscore );
									}
								}
							}
							else {
								if ( matches3[ 1 ] != undefined && ! jQuery.isNumeric( matches3[ 1 ] ) && matches3[ 1 ] != "-" ) {
									surveycatscore[ matches3[ 1 ] ] = parseFloat( surveycatscore[ matches3[ 1 ] ] ) + parseFloat( thisscore );
								}
							}
						}
				}
				if ( typeof question_score[ parseInt( played_question + 1 ) ] == "undefined" ) {
					question_score[ parseInt( played_question + 1 ) ] = 0;
				}
					surveyscore = parseInt( surveyscore ) + parseInt( thisscore );
					surveycorrect = parseInt( surveycorrect ) + parseInt( thiscorrect );
					question_score[ parseInt( played_question + 1 ) ] += parseInt( thisscore );
					question_correct[ parseInt( played_question + 1 ) ] = parseInt( thiscorrect );
					question_choice[ parseInt( played_question + 1 ) ] = answer;
				});
			}
			opentext = [];
			jQuery.each( answer, function( index, value ) {
				if ( modal_survey.find( "#survey_answer" + value ).hasClass( "survey_open_answers" ) ) {
					thistext = modal_survey.find( "#survey_answer" + value ).find( ".open_text_answer" ).val();
					if ( jQuery.inArray( thistext, opentext ) == -1 && thistext != 'null' ) {
						if ( thistext == "" && modal_survey.find( "#survey_answer" + value ).find( ".open_text_answer" ).hasClass( "numeric_answer" ) ) {
							thistext = 0;
						}
						opentext.push({
							unique: modal_survey.find( "#survey_answer" + value ).attr( "data-unique" ),
							value: thistext
						});
						survey.last_open = thistext;
					}
				}
			});
			
			if ( survey.preview == undefined ) {
				survey.preview = "false";
			}
			qa[ 'sid' ] = survey.survey_id;
			qa[ 'auto_id' ] = survey.auto_id;
			qa[ 'qid' ] = played_question + 1;
			qa[ 'aid' ] = answer;
			qa[ 'open' ] = opentext;
			qa[ 'postid' ] = survey.postid;
			qa[ 'time' ] = parseInt( survey.qtstart - survey.qtcurrent );
			qa[ 'played_question' ] = parseInt( played_question );
			qa[ 'endstep' ] = parseInt( endstep );
			qa[ 'questions_max' ] = survey.questions.length;
			thissurvey.push( qa );
			rmdni = true;
			endcontent = false;
			if ( ( played_question + 1 == endstep ) && ( ( survey_options[ 125 ] != 1 || survey.form == 'false' ) || survey.form != 'true' ) ) {
				endcontent = true;
			}
			var data = {
				action: 'ajax_survey_answer',
				sspcmd: 'save',
				endcontent: endcontent,
				options: JSON.stringify( thissurvey ),
				preview: survey.preview
				};
				jQuery.post( survey.admin_url, data, function( response ) {
					if ( response.toLowerCase().indexOf( "success" ) >= 0 ) {	
						jQuery( "#survey_preloader" ).remove();
						//make animation
						if ( sans != "" ) {
							played_question = parseInt(sans)-1;
						}
						else {
							played_question++;
						}
						continue_survey();
					}
					rmdni = false;
					sanswers = [];
				}).fail(function() {
						jQuery( "#survey_preloader" ).remove();						
						rmdni = false;
						sanswers = [];
				  })
		}
		function array_max( array ) {
			if ( Object.keys(array).length > 0 ) {
				return Object.keys( array ).reduce( function( a, b ){ return array[ a ] > array[ b ] ? a : b } );
			}
			else {
				return;
			}
		}		 
		function array_min( array ) {
			if ( Object.keys(array).length > 0 ) {
				return Object.keys( array ).reduce( function( a, b ){ return array[ a ] < array[ b ] ? a : b } );
			}
			else {
				return;
			}
		}
		function continue_survey() {
			if ( survey.style == 'flat' ) {
				if ( survey_options[ 158 ] == "fade" ) {
					modal_survey.fadeOut( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() { play_survey(); } );
				}
				else {
					modal_survey.slideUp( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() { play_survey(); });
				}
			}
			else {
				if ( survey_options[ 158 ] == "fade" ) {
						modal_survey.fadeOut( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() { play_survey(); } );
				}
				else {
					if ( survey_options[ 0 ] == "bottom" ) {
					modal_survey.animate({ bottom: "-" + parseInt( modal_survey.height() + 100 ) + "px" }, parseInt( survey_options[ 11 ]), survey_options[ 1 ], function(){
						play_survey();
						})
					}
					if ( survey_options[ 0 ] == "center" ) {
						if ( survey.align == "left" ) {
							modal_survey.animate({ left: "-5000px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
								play_survey();
								});
						}
						if ( survey.align == "right" ) {
							modal_survey.animate({ right: "-5000px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
								play_survey();
								});
						}
						if ( survey.align == "center" ) {
							modal_survey.animate({ top: "-1000px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
								play_survey();								
								});
						}
					}
					if ( survey_options[ 0 ] == "top" ) {
						modal_survey.animate({ top: "-" + parseInt( modal_survey.height() + 100 ) + "px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function(){
							play_survey();
							})
					}
				}
			}
		}
		jQuery( "body" ).on( "click", "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice", function() {
			if ( rmdni == false ) {
				if ( sanswers.length >= survey_qoptions[ played_question ][ 1 ] ) {
					send_answer( jQuery( this ), sanswers );
				}
				else {
					return;
				}
			}
			else {
				return true;
			}
		})
		jQuery( "body" ).on( "input change", "#survey-" + survey.survey_id + "-" + unique_key + " .open_text_answer", function() {
		  if ( ! jQuery( this ).hasClass( "select_answer" ) && ! jQuery( this ).hasClass( survey_options[ 134 ] + "selected" ) && jQuery( this ).val().length > 1 ) {
			jQuery( this ).parent().trigger( "click" );
		  }
		  if ( ! jQuery( this ).hasClass( "select_answer" ) && jQuery( this ).hasClass( survey_options[ 134 ] + "selected" ) && jQuery( this ).val().length < 2 ) {
			jQuery( this ).parent().trigger( "click" );
		  }
		});
		jQuery( "body" ).on( "click", ".select_answer", function( e ) {
			e.stopPropagation();
		});
		jQuery( "body" ).on( "input change", "#survey-" + survey.survey_id + "-" + unique_key + " .select_answer", function( e ) {
			if ( jQuery( this ).hasClass( "select_answer" ) ) {
			  if ( ! jQuery( this ).closest( ".survey_answers" ).hasClass( survey_options[ 134 ] + "selected" ) && jQuery( this ).val() != "" ) {
				jQuery( this ).closest( ".survey_answers" ).trigger( "click" );
			  }
			  if ( jQuery( this ).closest( ".survey_answers" ).hasClass( survey_options[ 134 ] + "selected" ) && jQuery( this ).val() == "" ) {
				  jQuery( this ).closest( ".survey_answers" ).trigger( "click" );
			  }
			}
		});
		jQuery( "body" ).on( "click", "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answers", function( e ) {
		var thisanswer = jQuery( this ), pq, thisqid;
		msvoted = true;
		if ( jQuery( e.target ).hasClass( "select_answer" ) ) {
			return;
		}
		if ( ! jQuery( this ).hasClass( 'survey_answer_choice' ) ) {
			sanswers = [];
		}
		if ( ! thisanswer.hasClass( "selected" ) ) {
			if ( survey.display != 'all' && ! jQuery.isNumeric( survey.display ) && ( survey.display.indexOf( "," ) == -1 ) && sanswers.length == survey_qoptions[ played_question ][ 1 ] ) {
				rmdni = false;
				return true;
			}
		}
		thisqid = jQuery( this ).attr( "qid" );
		jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answers[qid=" + thisqid + "]" ).each( function( index ) {
			if ( ( jQuery( this ).hasClass( survey_options[ 134 ] + "selected" ) ) && ( jQuery( this ).attr( "id" ) != undefined ) ) {
				if ( ! sanswers.indexOf( jQuery( this ).attr( "id" ).replace( "survey_answer", "" ) ) >= 0 ) {
					sanswers.push( jQuery( this ).attr( "id" ).replace( "survey_answer", "" ) );
				}
			}
		})
		if ( ! thisanswer.hasClass( ".survey_rating" ) ) {
			if ( thisanswer.attr( "class" ).indexOf( "survey_answer_choice" ) > 0 ) return;
			if ( thisanswer.children( ".open_text_answer" ).length > 0 ) {
				/* Minimum length for open text answer */
				if ( thisanswer.children( ".open_text_answer" ).val().length < 1 ) return;
			}
		}
			if ( survey.display == 'all' || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) {
				pq = jQuery( this ).attr( "qid" );
			}
			else {
				pq = played_question;				
			}
			if ( ( survey_qoptions[ pq ][ 0 ] > 1 || survey.questions[ pq ][ 'hasopen' ] == true || ( survey.display == 'all' || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) ) || survey_options[ 152 ] == 1 && ( survey_qoptions[ pq ][ 3 ] != '1' ) ) {
				if ( ! thisanswer.hasClass( "survey_open_answers" ) && survey_qoptions[ pq ][ 0 ] < 2 && thisanswer.attr( "id" ) != undefined && survey_options[ 152 ] != 1 ) {
					sanswers = [ thisanswer.attr( "id" ).replace( "survey_answer", "" ) ];
					send_answer( thisanswer, sanswers );
				}
				rmdni = true;
				if ( thisanswer.hasClass( survey_options[ 134 ] + "selected" ) ) {
					if ( ! thisanswer.hasClass( ".survey_rating" ) ) {
						thisanswer.removeClass( survey_options[ 134 ] + "selected" );
						thisanswer.children(".open_text_answer" ).removeClass( survey_options[ 134 ] + "selected" );
					}
					if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).length > 0 ) {
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).attr( "data-qstep", "" );
					}
					sanswers = jQuery.grep( sanswers, function( value ) {
						if ( thisanswer.attr( "id" ) != undefined ) {
							return value != thisanswer.attr( "id" ).replace( "survey_answer", "" );
						}
					});
				}
				else {
					if ( sanswers.length >= survey_qoptions[ pq ][ 0 ] && survey_qoptions[ pq ][ 0 ] > 1 ) {
						rmdni = false;
						return;
					}
					if ( sanswers.length >= survey_qoptions[ pq ][ 0 ] && survey_qoptions[ pq ][ 0 ] < 2 && survey.display != "all" && ! jQuery.isNumeric( survey.display ) && ( survey.display.indexOf( "," ) < 0 ) ) {
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answers[qid=" + thisqid + "]" ).each( function( index ) {
							if ( ( jQuery( this ).hasClass( survey_options[ 134 ] + "selected" ) ) && ( thisqid == jQuery( this ).attr( "qid" ) ) ) {
								if ( ! thisanswer.hasClass( ".survey_rating" ) ) {
									jQuery( this ).removeClass( survey_options[ 134 ] + "selected" );
								}
							}
						})
					}
					if ( sanswers.length >= survey_qoptions[ pq ][ 0 ] && survey_qoptions[ pq ][ 0 ] < 2 && ( survey.display == "all" || jQuery.isNumeric( survey.display ) || survey.display.indexOf( "," ) >= 0 ) ) {
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answers[qid=" + thisqid + "]" ).each( function( index ) {
							if ( ( jQuery( this ).hasClass( survey_options[ 134 ] + "selected" ) ) && ( thisqid == jQuery( this ).attr( "qid" ) ) ) {
								if ( ! thisanswer.hasClass( ".survey_rating" ) ) {
									jQuery( this ).removeClass( survey_options[ 134 ] + "selected" );
								}
							}
						})
					}
					if ( sanswers.length == survey_qoptions[ pq ][ 0 ] && survey_qoptions[ pq ][ 0 ] > 1 ) {
						return;
					}
					thisanswer.addClass( survey_options[ 134 ] + "selected" );
					thisanswer.children(".open_text_answer" ).addClass( survey_options[ 134 ] + "selected" );
					if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).length > 0 ) {
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).attr( "data-qstep", thisanswer.attr( "data-qstep" ) );
					}
					if ( thisanswer.attr( "id" ) != undefined ) {
						sanswers.push( thisanswer.attr( "id" ).replace( "survey_answer", "" ) );
					}
					//opentext.push( thisanswer.children( ".open_text_answer" ).val() );
				}
				if ( sanswers.length > survey_qoptions[ pq ][ 0 ] ) {
					sanswers.shift();
				}
				rmdni = false;
				if ( sanswers.length >= survey_qoptions[ pq ][ 1 ] ) {
					jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).children( "img" ).attr( "src", survey.plugin_url + "/templates/assets/img/" + themeprefix + "next.png" );
						if ( ! thisanswer.hasClass( ".survey_rating" ) ) {
							jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).removeClass( "passive" ).addClass( "active" );
						}
				}
				else {
					jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).children( "img" ).attr( "src", survey.plugin_url + "/templates/assets/img/" + themeprefix + "next-passive.png" );
						if ( ! thisanswer.hasClass( ".survey_rating" ) ) {
							jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answer_choice" ).removeClass( "active" ).addClass( "passive" );
						}
				}
			}
			else {
				if ( rmdni == false && survey.display != "all" && ! jQuery.isNumeric( survey.display ) ) {
					if ( thisanswer.attr( "id" ) != undefined ) {
						sanswers = [ thisanswer.attr( "id" ).replace( "survey_answer", "" ) ];
						send_answer( thisanswer, sanswers );
					}
				}
				else {
					return true;
				}
			}
			if ( survey.display == '1' && survey_qoptions[ pq ][ 0 ] < 2 && survey_qoptions[ pq ][ 1 ] < 2 && jQuery( this ).hasClass( "single" ) && ! jQuery( this ).hasClass( "survey_open_answers" ) ) {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-next-question" ).trigger( "click" );
			}
		})

		function setCookie( params ) {
		var c_name = params[ 0 ], value = params[ 1 ], dduntil = params[ 2 ], mode = params[ 3 ];
			if ( value === undefined ) {
				return true;
			}
			if ( mode == 'days' ) {
				var exdate = new Date();
				exdate.setDate( exdate.getDate() + parseInt( dduntil ) );
				var c_value = escape( value ) + ( ( dduntil == null ) ? "" : "; expires=" + exdate.toUTCString() ) + "; path=/";
				document.cookie = c_name + "=" + c_value;
			}
			if (mode=='minutes') {
				var exdate = new Date();
				exdate.setMinutes( exdate.getMinutes() + parseInt( dduntil ) )
				var c_value = escape( value ) + ( ( dduntil == null ) ? "" : "; expires=" + exdate.toUTCString() ) + "; path=/";
				document.cookie = c_name + "=" + c_value;
			}
		}
		function getCookie( c_name ) {
			c_value = document.cookie;
			c_start = c_value.indexOf( " " + c_name + "=" );
			if ( c_start == -1 ) {
				c_start = c_value.indexOf( c_name + "=" );
			}
			if ( c_start == -1 ) {
				c_value = null;
			}
			else {
				c_start = c_value.indexOf( "=", c_start ) + 1;
				c_end = c_value.indexOf( ";", c_start );
				if ( c_end == -1 ) {
					c_end = c_value.length;
				}
				c_value = unescape( c_value.substring( c_start, c_end ) );
			}
			return c_value;
		}
		jQuery( document ).on( "mouseover", "#survey-" + survey.survey_id + "-" + unique_key + " .survey_rating", function() {
			var thisid = parseInt( jQuery( this ).attr( "id" ).replace( "survey_answer", "" ) );
			var parentitem = jQuery( this ).parent().parent();
			currenthover = jQuery( this );
				for( var i = thisid; i > 0; i-- ) {
					if ( parentitem != undefined ) {
						parentitem.find( "#survey_answer" + i ).each( function( index ) {
							jQuery( this ).css( "background-image", "url(" + survey.plugin_url + "/templates/assets/img/" + survey.rating_icon + "-icon.png)" );
						})
					}
				}
			currenthover.mouseleave( function() {
				if ( rating_selected[ parentitem.attr( "qid" ) ] == undefined ) {
					if ( parentitem != undefined ) {
						parentitem.find( ".survey_rating" ).css( "background-image", "url(" + survey.plugin_url + "/templates/assets/img/" + survey.rating_icon + "-icon-empty.png)" );
					}
				}
				else {
					for( var i = thisid; i > rating_selected[ parentitem.attr( "qid" ) ]; i-- ) {
						if ( parentitem != undefined ) {
							parentitem.find( "#survey_answer" + i ).each( function( index ) {
								jQuery( this ).css( "background-image", "url(" + survey.plugin_url + "/templates/assets/img/" + survey.rating_icon + "-icon-empty.png)" );
							})
						}
					}
				}
			})
	   });  
		jQuery( document ).on( "click touchend", "#survey-" + survey.survey_id + "-" + unique_key + " .survey_rating", function() {
			var thisid = parseInt( jQuery( this ).attr( "id" ).replace( "survey_answer", "" ) );
			var parentitem = jQuery( this ).parent().parent();
			parentitem.find( ".survey_rating" ).removeClass( "selected" );
			jQuery( this ).addClass( "selected" );
			parentitem.find( ".survey_rating" ).css( "background-image", "url(" + survey.plugin_url + "/templates/assets/img/" + survey.rating_icon + "-icon-empty.png)" );
			rating_selected[ jQuery( this ).attr( "qid" ) ] = thisid;
			for( var i = thisid; i > 0; i-- ) {
					parentitem.find( "#survey_answer" + i ).each( function( index ) {
						jQuery( this ).css( "background-image", "url(" + survey.plugin_url + "/templates/assets/img/" + survey.rating_icon + "-icon.png)" );
					})
			}
      })
	   
	   function make_animation() {
			if ( survey.style == 'flat' ) {
				if ( detectmob() && survey.scrollto != 'false' ) {
					jQuery( "html, body" ).animate({
							scrollTop: modal_survey.offset().top - 200
					}, 500 );
				}
				if ( survey_options[ 158 ] == "fade" ) {
					modal_survey.fadeIn( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
						display_chart();
						if ( endstep == played_question && ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) ) {
							jQuery( "html, body" ).animate({
									scrollTop: modal_survey.offset().top - 200
								}, 200 );
						}
						modal_survey.hide().show(0);						
					} );
				}
				else {
					modal_survey.slideDown( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
						display_chart();
						if ( endstep == played_question && ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) ) {
							jQuery( "html, body" ).animate({
									scrollTop: modal_survey.offset().top - 200
								}, 200 );					
						}
						modal_survey.hide().show(0);
					});
				}
			}
			else {
				if ( survey_options[ 158 ] == "fade" ) {
					if ( survey_options[ 0 ] == "bottom" ) {
						modal_survey.css({ "bottom": "10px", "display": "none" });
						modal_survey.fadeIn( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {display_chart();} )
					}
					if ( survey_options[ 0 ] == "center" ) {
						if ( survey.align == "left" ) {
							modal_survey.css({ "left": "0px", "top": ( ( jQuery( window ).height() - modal_survey.height() ) / 2 ) + "px", "display": "none" });
							modal_survey.fadeIn( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {display_chart();} );
						}
						if ( survey.align == "right" ) {
							modal_survey.css({ "right": "0px", "top": ( ( jQuery( window ).height() - modal_survey.height() ) / 2 ) + "px", "display": "none" });
							modal_survey.fadeIn( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {display_chart();} );
						}
						if ( survey.align == "center" ) {
							modal_survey.css({ "top": ( ( jQuery( window ).height() - modal_survey.height() ) / 2 ) + "px", "display": "none" });
							modal_survey.fadeIn( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {display_chart();} );
						}
					}
					if ( survey_options[ 0 ] == "top" ) {
						modal_survey.css({ "top": "10px", "display": "none" });
						modal_survey.fadeIn( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {display_chart();} );
					}					
				}
				else {
					if ( survey_options[ 0 ] == "bottom" ) {
						modal_survey.css( "bottom", "-" + parseInt( modal_survey.height() + 100 ) + "px" );
						modal_survey.animate( { bottom: '10px' }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {display_chart();} )
					}
					if ( survey_options[ 0 ] == "center" ) {
						if ( survey.align == "left" ) {
							modal_survey.css( "left", "-5000px" );
							modal_survey.css( "top", ( ( jQuery( window ).height() - modal_survey.height() ) / 2 ) + "px" );
							modal_survey.animate({ left: "0px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {display_chart();} );
						}
						if ( survey.align == "right" ) {
							modal_survey.css( "right", "-5000px" );
							modal_survey.css( "top", ( ( jQuery( window ).height() - modal_survey.height() ) / 2 ) + "px" );
							modal_survey.animate({ right: "0px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {display_chart();} );
						}
						if ( survey.align == "center" ) {
							modal_survey.css( "top", "-1000px" );
							modal_survey.animate({ top: ( ( jQuery( window ).height() - modal_survey.height() ) / 2 ) + "px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {display_chart();} );
						}
					}
					if ( survey_options[ 0 ] == "top" ) {
						modal_survey.css( "top", "-" + parseInt( modal_survey.height() + 100 ) + "px" );
						modal_survey.animate({ top: '10px' }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {display_chart();} );
					}
				}
			}
	   }
		 
		function set_survey_style() {
			bgs = survey_options[ 3 ].split(";");
			for ( i = 0; i < bgs.length - 1; ++i ) {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_question, #survey-" + survey.survey_id + "-" + unique_key + " .survey_endcontent, #survey-" + survey.survey_id + "-" + unique_key + " .section_content" ).css( "background", jQuery.trim( bgs[ i ] ) );
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answers" ).css( "background", jQuery.trim( bgs[ i ] ) );
			}
			jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_question, #survey-" + survey.survey_id + "-" + unique_key + " .survey_endcontent, #survey-" + survey.survey_id + "-" + unique_key + " .section_content" ).css({ 
				"color": survey_options[ 4 ], 
				"border": "solid " + survey_options[ 6 ] + "px " + survey_options[ 5 ], 
				"padding": survey_options[ 9 ] + "px", 
				"font-size": survey_options[ 8 ] + "px", 
				"border-radius": survey_options[ 7 ] + "px"
			});
			jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answers" ).css({ 
				"color": survey_options[ 4 ], 
				"border": "solid " + survey_options[ 6 ] + "px " + survey_options[ 5 ], 
				"padding": survey_options[ 9 ] + "px", 
				"font-size": survey_options[ 8 ] + "px", 
				"border-radius": survey_options[ 7 ] + "px"
			});
			jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answers input, #survey-" + survey.survey_id + "-" + unique_key + " .survey_answers select" ).css({ 
				"color": survey_options[ 4 ], 
				"font-size": survey_options[ 8 ] + "px", 
				"background": "transparent"
			});
			if ( survey.questionbg != "false" ) {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_question, #survey-" + survey.survey_id + "-" + unique_key + " .section_content" ).css( "background", survey.questionbg );
			}
			if ( survey.boxed != "false" ) {
				if ( survey.boxed == "true" ) {
					boxed[ 0 ] = '#eee';
					boxed[ 1 ] = '30px';
					boxed[ 2 ] = '5px';
				}
				else {
					if ( typeof survey.boxed != "undefined" ) {
						boxed = survey.boxed.replace(/ /g,'').split( "," );
						if ( boxed[ 0 ] == undefined ) {
							boxed[ 0 ] = '#eee';				
						}
						if ( boxed[ 1 ] == undefined ) {
							boxed[ 1 ] = '30px';				
						}
						if ( boxed[ 2 ] == undefined ) {
							boxed[ 2 ] = '3px';				
						}
					}
				}
				modal_survey.css({
					"background": boxed[ 0 ],
					"padding": boxed[ 1 ],
					"border-radius": boxed[ 2 ]
				});
			}
			jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_question, #survey-" + survey.survey_id + "-" + unique_key + " .section_content, #survey-" + survey.survey_id + "-" + unique_key + " .survey_endcontent, #survey-" + survey.survey_id + "-" + unique_key + " .survey_answers" ).css({
				"box-shadow": survey_options[ 128 ] + "px " + survey_options[ 129 ] + "px " + survey_options[ 130 ] + "px " + survey_options[ 131 ] + "px " + survey_options[ 132 ]
			});
			jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_rating" ).css({
				"box-shadow": "0px 0px 0px 0px transparent"				
			});
			if ( survey_options[ 16 ] != undefined ) {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_element" ).css( "text-align", survey_options[ 16 ] );
			}
			if ( survey.textalign != "" ) {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_element" ).css( "text-align", survey.textalign );
			}

			if ( survey_options[ 2 ] != "" ) {
				if ( !jQuery( "link[href='" + protocol + "fonts.googleapis.com/css?family=" + survey_options[ 2 ] + "']" ).length ) {
					jQuery('head').append('<link rel="stylesheet" href="' + protocol + 'fonts.googleapis.com/css?family='+survey_options[2].replace(' ','+').replace(' ','+').replace(' ','+')+':400,700" type="text/css" />');
				}
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_element" ).css( "font-family", "'" + survey_options[ 2 ] + "', serif" );
			}
			if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_rating" ) != undefined ) {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_rating" ).css( "background-image", "url( '" + survey.plugin_url + "/templates/assets/img/" + survey.rating_icon + "-icon-empty.png' )" );
			}
			jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .nextstyle2, #survey-" + survey.survey_id + "-" + unique_key + " .backstyle2" ).css( {
				"background": "",
				"color": "",
				"border": "",
				"padding": "",
				"fontSize": ""
			});
			jQuery( ".ms-next-question, .ms-next-back" ).css({
				"borderRadius": survey_options[ 7 ] + "px"
			})
		}
		 
		function quizcountdown() {
			if ( pformstep == played_question ) {
				abortTimer();
				return;
			}
			survey.qtid = setTimeout( quizcountdown, 1000 );
			survey.qtcurrent--;
			modal_survey.find( ".quiztimer-container .qtdisplay" ).html( survey.qtcurrent );		  
			if ( survey.qtcurrent < 1 ) {
				if ( survey.quiztimertype == 1 ) {
					played_question++;
					abortTimer();
					continue_survey();
				}
				else {
					abortTimer();
					played_question = survey.questions.length;
					continue_survey();
				}
			}
		}
		function abortTimer() {
		  clearTimeout( survey.qtid );
		}
		
		function initialize_special_answers() {
			jQuery( ".modal-survey-container" ).addClass( "ms-jquery-ui" );
			if ( modal_survey.find( ".numeric_answer_slider" ).length > 0 ) {
				modal_survey.find( ".numeric_answer_slider" ).each( function( index ) {
					jQuery( this ).slider({
						range: "min",
						step: parseFloat( jQuery( this ).attr( "data-step" ) ),
						value: parseFloat( jQuery( this ).attr( "data-min" ) ),
						min: parseFloat( jQuery( this ).attr( "data-min" ) ),
						max: parseFloat( jQuery( this ).attr( "data-max" ) ),
						slide: function( event, ui ) {
							jQuery( ui.handle ).parent().parent().find( ".numeric_answer" ).val( function( index, value ) {
							   return value.replace( jQuery( ui.handle ).parent().parent().find( ".numeric_answer" ).val().replace( /[^\d.]/g, '' ), ui.value );
							});
						},
						stop:  function( event, ui ) {
							setTimeout( function() {
								if ( ! jQuery( ui.handle ).parent().parent().hasClass( survey_options[ 134 ] + "selected" ) ) {
										jQuery( ui.handle ).parent().parent().trigger( "click" );
								}
							}, 100 );
						}
					});
				})
			}
			if ( jQuery( ".date_answer" ).length > 0 ) {
				jQuery( ".date_answer" ).datepicker({
					changeMonth: true,
					changeYear: true,
					showOtherMonths: true,
					yearRange: "-100:+0",
					selectOtherMonths: true,
					beforeShow: function() {
							jQuery( this ).datepicker( "widget" ).wrap( "<div class='ms-jquery-ui'></div>" );
					},
					onSelect: function( date, inst ) {
						if ( ! jQuery( inst.input ).parent().parent().hasClass( survey_options[ 134 ] + "selected" ) ) {
							jQuery( inst.input ).parent().parent().trigger( "click" );
						}
					}
				});
			}
		}
	
		function play_survey() {
			var survey_content = "", remove_image = "", percent = 0, percent_last = 0, survey_progress_bar = "", bgs, i, key, qstep, oindex, next_button = "", back_button = "", answerimg = "", qimg = "", tooltip, tableclass, pansnum, qtooltip, dividequestions, dividequestions_end, section_content;
			qtextspanclass = "", atextspanclass = "", answer_ma_class = "";
			survey.qtstart = survey.qtcurrent;
			if ( survey.pform == "start" && pform == false ) {
				played_question = survey.qo.length;
			}
			if ( survey.pform == "start" && pform == true && played_question == pformstep ) {
				played_question++;
				var data = {
						action: 'ajax_survey_answer',
						sspcmd: 'form',
						pform: pform,
						pform_pos: survey.pform,
						endcontent: true,
						conf: '',
						name: '',
						email: '',
						sid: survey.survey_id,
						postid: survey.postid,
						cae: survey.cae
					};
				jQuery.post( survey.admin_url, data, function( response ) {
				})
			}
			if ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) {}
			else {
				if ( parseInt( survey.lastsessionqid ) > 0 && survey_options[ 155 ] == "1" ) {
					if ( parseInt( survey.lastsessionqid ) < survey.questions.length ) {
						played_question = parseInt( survey.lastsessionqid );
						survey.lastsessionqid = -1;
					}
				}
				if ( getCookie( "ms-cqn-" + survey.survey_id ) > 0 && survey_options[ 155 ] == "1" && msvoted == false && played_question == 0 ) {
					played_question = parseInt( getCookie( "ms-cqn-" + survey.survey_id ) );
					msparams = [ 'ms-cqn-' + survey.survey_id, "", -1, 'days' ];
					setCookie( msparams );
				}
				if ( survey_options[ 155 ] == "1" ) {
					msparams = [ 'ms-cqn-' + survey.survey_id, played_question, 365, 'days' ];
					setCookie( msparams );
				}
			}
			if ( played_question < 0 ) {
				return true;
			}
			pformstep = survey.questions.length;
			if ( survey_qoptions[ played_question ] != undefined ) {
				if ( survey_qoptions[ played_question ][ 4 ] != "" && survey_qoptions[ played_question ][ 4 ] != undefined ) {
					qtooltip = ' data-tooltip="' + survey_qoptions[ played_question ][ 4 ] + '"';
				}
				else {
					qtooltip = '';
				}
			}
			if ( played_question > endstep ) {
				played_question = endstep;
			}
			if ( ( survey.display == 'all' || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) && endstep > played_question ) {
				survey_content = alldisplay_survey_content;
			}
			if ( jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) || survey.display == "all" ) {
				dividequestions = "<div class='each-question ms-qs" + parseInt( played_question + 1 ) + "'>";
				dividequestions_end = "</div>";
			}
			else {
				dividequestions = "";
				dividequestions_end = "";
			}
			if ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) {
				tableclass = "alldisplay";
			}
			else {
				tableclass = "eachdisplay";						
			}
			if ( ( ( survey_options[ 125 ] == 1 && survey.form != 'false' ) || survey.form == 'true' ) && ( survey_options[ 126 ] == 1 || survey_options[ 127 ] == 1 ) ) {
				endstep = pformstep + 1;
			}
			else {
				endstep = survey.questions.length;
				if ( survey.confirmation == "true" ) {
					endstep++;
				}
			}
			if ( survey.display != "1" ) {
				if ( survey_options[ 20 ] == "1" && ( survey.questions.length >= played_question ) ) {
					percent = Math.ceil( ( played_question / survey.questions.length ) * 100 );
					survey_progress_bar = '<div class="survey-progress-bar"><div class="survey-progress-ln"><span class="progress_counter">0%</span></div><div class="survey-progress-ln2"><span class="progress"></span></div></div>';
					if ( played_question > 1 ) {
						percent_last = Math.ceil( ( parseInt( played_question - 1 ) / parseInt( survey.questions.length ) ) * 100 );
						if ( jQuery.isNumeric( survey.display ) && survey.display > 1 ) {				
							if ( played_section == 1 ) {
								percent_last = 0;
							}
							else if ( played_section == parseInt( endstep / survey.display ) ) {
								percent_last = 100;
							}
							else {
								percent_last = parseInt( played_section * ( 100 / ( endstep / survey.display ) ) );
							}
						}
						survey_progress_bar = '<div class="survey-progress-bar"><div class="survey-progress-ln"><span class="progress_counter">' + percent_last + '%</span></div><div class="survey-progress-ln2"><span class="progress" style="width: ' + percent_last + '%"></span></div></div>';
					}
					if ( played_question == 1 ) {
						percent_last = 0;
					}
				}
				else {
					survey_progress_bar = "";
				}
			}
			//modal_survey.find( ".progress" ).css( "width", percent_last + "%" );
			if ( played > 0 && ( survey.style == 'click' ) ) {
				survey.expired = "true";
				if ( survey.message == undefined ) {
					survey.message = survey.languages.alreadyfilled;
				}
			}
			if ( ( survey.expired == 'true' ) && ( survey.style != 'click' ) ) {
				return true;
			}
			if ( typeof survey.questions[ played_question ] != 'undefined' ) {
				survey.questions[ played_question ][ 'hasopen' ] = false;
			}
			if ( ( ( survey.questions.length > 0 ) || ( survey.questions.length < played_question + 1 ) ) || ( survey.expired == 'true' ) ) {
				if ( survey.style != 'flat' ) {
					modal_survey.css( "top", "" );
					modal_survey.css( "bottom", "" );
					modal_survey.css( survey.align, "0px" );
					if ( parseInt( survey_options[ 14 ] ) == 1 ) {
						if ( survey_options[ 0 ] == "top" ) {
							remove_image = "<img id='close_survey' class='cl_top_survey closeimg_" + survey_options[ 145 ] + "' src='" + survey.plugin_url + "/templates/assets/img/" + survey_options[ 141 ] + ".png' />";
						}
						else {
							remove_image = "<img id='close_survey' class='cl_survey closeimg_" + survey_options[ 145 ] + "' src='"+survey.plugin_url + "/templates/assets/img/" + survey_options[ 141 ] + ".png' />";
						}
					}
				}
				if ( ( survey.questions.length - 1 ) >= played_question && ( survey.expired != 'true' ) ) {
					if ( isUrlValid( survey_qoptions[ played_question ][ 2 ] ) ) {
						if ( survey_qoptions[ played_question ][ 6 ] != "" ) {
							qimgwidth = survey_qoptions[ played_question ][ 6 ];
						}
						else {
							qimgwidth = "";
						}
						if ( survey_qoptions[ played_question ][ 7 ] != "" ) {
							qimgheight = survey_qoptions[ played_question ][ 7 ];
						}
						else {
							qimgheight = "";
						}
						qimgstyle = "";
						if ( qimgwidth != "" && qimgheight == "" ) {
							qimgstyle = "style='width: " + qimgwidth + ";max-width: " + qimgwidth + "'";
						}
						if ( qimgwidth == "" && qimgheight != "" ) {
							qimgstyle = "style='height: " + qimgheight + ";max-height: " + qimgheight + "'";
						}
						if ( qimgwidth != "" && qimgheight != "" ) {
							qimgstyle = "style='width: " + qimgwidth + ";max-width: " + qimgwidth + ";height: " + qimgheight + ";max-height: " + qimgheight + "'";
						}
						if ( location.protocol == 'https:') {
							survey_qoptions[ played_question ][ 2 ] = survey_qoptions[ played_question ][ 2 ].replace( "http:", "https:" );
						}
						qimg = "<img " + qimgstyle + " class='survey_question_image' src='" + survey_qoptions[ played_question ][ 2 ] + "'>";
					}
					else {
						qimg = "";
					}
					chtitle = survey.questions[ played_question ][ 0 ].match(/\[(.*)\]/);
					if ( chtitle != null ) {
						question_name[ parseInt( played_question ) ] = chtitle[ 1 ];
					}
					else {
						question_name[ parseInt( played_question ) ] = survey.questions[ played_question ][ 0 ];
					}
					quizcont = '';
					if ( survey_options[ 156 ] > 0 && survey.display != "all" && ! jQuery.isNumeric( survey.display ) && ( survey.display.indexOf( "," ) < 0 ) ) {
						quizcont = '<div class="quiztimer-container uil-ring-css" style="transform:scale(0.6);"><div class="quiztimer"></div><div class="qtdisplay">' + parseInt( survey.qtcurrent ) + '</div></div>';
						if ( survey.quiztimertype == 1 ) {
							survey.qtcurrent = survey.quiztimer;
						}
					}
					survey_content += remove_image + quizcont;
					if ( jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) || tableclass == "alldisplay" ) {
						survey_content += dividequestions;
					}
					if ( survey_qoptions[ played_question ][ 8 ] == "3" ) {
						qtextspanclass = ' class="text-on-top"';
					}
					else {
						qtextspanclass = '';						
					}
					if ( survey_options[ 163 ] == '1' && admin_comment != "" && admin_comment != null ) {
						admin_comment_container = "<div class='admin-comment-container'>" + admin_comment + "</div>";
					}
					else {
						admin_comment_container = "";
					}
					if ( survey_qoptions[ played_question ][ 9 ] != "" && survey_qoptions[ played_question ][ 9 ] != undefined  ) {
						section_content = '<div class="section_content section_content-' + played_question + ' survey_element">' + survey_qoptions[ played_question ][ 9 ] + '</div>';
					}
					else {
						section_content = "";
					}
					survey_content += section_content + '<div class=" survey_table ' + tableclass + '"><div class="survey_element survey_question sq' + played_question + ' active_question ms-imgpos' + survey_qoptions[ played_question ][ 8 ] + '" ' + qtooltip + '>'  +  qimg + '<span' + qtextspanclass + '>' + admin_comment_container + survey.questions[ played_question ][ 0 ].replace(/\[(.*?)\]/g, "") + '</span></div>';
					var answers_number = -2, separator, different = 0, counter = 0, qstepn, acat;
					for ( i in survey.questions[ played_question ] ) {
						if ( survey.questions[ played_question ].hasOwnProperty( i ) ) {
							answers_number++;
						}
					}
					if ( answers_number < 5 || answers_number % 4 == 0 ) {
						separator = 4;
					}
					if ( answers_number % 3 == 0 ) {
						separator = 3;
					}
					if ( answers_number % 4 > 0 ) {
						separator = 4;
						different = answers_number % 4;
					}
					if ( answers_number % 3 > 0 ) {
						separator = 3;
						different = answers_number % 3;
					}
					if ( survey.grid_items > 0 && detectmob() != true ) {
						separator = survey.grid_items;
						different = answers_number % survey.grid_items;
					}
					if ( detectmob() == true || ( modal_survey.parent().width() < 500 && survey.style == 'flat' ) ) {
						separator = 1;
						different = 0;
					}
					if ( survey_options[ 22 ] == 1 ) {
						separator = 1;
					}
					for ( key in survey.questions[ played_question ] ) {
						if ( key != 0 && jQuery.isNumeric( key ) ) {
							// starting new line if it is not rating question
							if ( ( counter == 0 ) && ( survey_qoptions[ played_question ][ 3 ] != 1 ) ) {
								survey_content += '</div>';
								survey_content += '<div class="survey_table qt' + played_question + '">';
							}
							//answer counter
							counter++;
							qstep = survey.questions[ played_question ][ key ].match(/\[([(0-9)])+\]/g);
							if ( qstep != null ) {
							qstep[ 1 ] = qstep[ 0 ].replace( "[", "" ).replace( "]", "" )
								if ( qstep[ 1 ] >= 0 ) {
									qstepn = qstep[ 1 ];
								}
								else {
									acat = qstep[ 1 ];
									qstepn = '';
								}
							}
							else {
								qstepn = '';
							}
							oindex = parseInt( played_question + 1 ) + "_" + key;

							if ( survey.removesame == "true" ) {
								/* remove same answers if already selected based on the IDs */
								jQuery.each( question_choice, function( index, value ) {
									jQuery.each( value, function( ind, val ) {
										if ( key == val ) {
											survey_aoptions[ oindex ][ 8 ] = 1;
										}
									} )
								} )
							}
							if ( qstepn == '' && survey_aoptions[ oindex ][ 11 ] != undefined && survey_aoptions[ oindex ][ 11 ] != '' && jQuery.isNumeric( survey_aoptions[ oindex ][ 11 ] ) ) {
								qstepn = survey_aoptions[ oindex ][ 11 ];
							}
							if ( survey_aoptions[ oindex ][ 8 ] == 1 ) {
								answer_status = " inactive_msanswer";
							}
							else {
								answer_status = "";
							}
							if ( survey_aoptions[ oindex ][ 10 ] != "" && survey_aoptions[ oindex ][ 10 ] != undefined ) {
								tooltip = ' data-tooltip="' + survey_aoptions[ oindex ][ 10 ] + '"';
							}
							else {
								tooltip = '';
							}
							//processing default questions
							if ( survey_qoptions[ played_question ][ 3 ] != 1 ) {
									if ( isUrlValid( survey_aoptions[ oindex ][ 3 ] ) ) {
										if ( survey_aoptions[ oindex ][ 6 ] != "" ) {
											imgwidth = survey_aoptions[ oindex ][ 6 ];
										}
										else {
											imgwidth = "";
										}
										if ( survey_aoptions[ oindex ][ 7 ] != "" ) {
											imgheight = survey_aoptions[ oindex ][ 7 ];
										}
										else {
											imgheight = "";
										}
										imgstyle = "";
										if ( imgwidth != "" && imgheight == "" ) {
											imgstyle = "style='width: " + imgwidth + ";max-width: " + imgwidth + "'";
										}
										if ( imgwidth == "" && imgheight != "" ) {
											imgstyle = "style='height: " + imgheight + ";max-height: " + imgheight + "'";
										}
										if ( imgwidth != "" && imgheight != "" ) {
											imgstyle = "style='width: " + imgwidth + ";max-width: " + imgwidth + ";height: " + imgheight + ";max-height: " + imgheight + "'";
										}
										if ( location.protocol == 'https:') {
											survey_aoptions[ oindex ][ 3 ] = survey_aoptions[ oindex ][ 3 ].replace( "http:", "https:" );
										}
											answerimg = "<img class='survey_answer_image' " + imgstyle + " src='" + survey_aoptions[ oindex ][ 3 ] + "'>";
									}
									else {
										answerimg = "";
									}
									if ( survey_aoptions[ oindex ][ 14 ] == "3" ) {
										atextspanclass = ' text-on-top';
									}
									else {
										atextspanclass = '';						
									}
									//checking multiple answers allowed
									if ( survey_qoptions[ played_question ][ 0 ] > 1 ) {
										answer_ma_class = "multiple"; 
									}
									else {
										answer_ma_class = "single"; 										
									}
									//generating open text answer
									if ( survey_aoptions[ oindex ][ 0 ] == "open" ) {
										if ( survey_aoptions[ oindex ][ 9 ] == 1 ) {
											//print textarea
											survey_content += '<div ' + tooltip + ' class="survey_element survey_answers ' + answer_ma_class + ' ' + survey_options[ 134 ] + ' survey_open_answers ' + answer_status + ' ms-imgpos' + survey_aoptions[ oindex ][ 14 ] + '" onclick="" data-unique="' + survey_aoptions[ oindex ][ 1 ] + '" data-qstep="' + qstepn + '" qid="' + played_question + '" id="survey_answer' + ( parseInt( key ) ) + '">' + answerimg + '<textarea maxlength="600" name="open_answer" class="open_text_answer open_def" placeholder="' + survey.questions[ played_question ][ key ].replace( /\[(.*)\]/, '' ) + '"></textarea></div>';
										}
										else {
											//print text input
											survey_content += '<div ' + tooltip + ' class="survey_element survey_answers ' + answer_ma_class + ' ' + survey_options[ 134 ] + ' survey_open_answers ' + answer_status + ' ms-imgpos' + survey_aoptions[ oindex ][ 14 ] + '" onclick="" data-unique="' + survey_aoptions[ oindex ][ 1 ] + '" data-qstep="' + qstepn + '" qid="' + played_question + '" id="survey_answer' + ( parseInt( key ) ) + '">' + answerimg + '<input' + qtooltip + ' list="ms_answers_' + survey.survey_id + '_' + survey_aoptions[ oindex ][ 1 ] + '" type="text" autocomplete="off" maxlength="600" name="open_answer" class="open_text_answer open_def" value="" placeholder="' + survey.questions[ played_question ][ key ].replace( /\[(.*)\]/, '' ) + '"></div>';
										}
										survey.questions[ played_question ][ 'hasopen' ] = true;
									}
									else if ( survey_aoptions[ oindex ][ 0 ] == "date" ) {
										//print text input
										survey_content += '<div ' + tooltip + ' class="survey_element survey_answers ' + answer_ma_class + ' ' + survey_options[ 134 ] + ' survey_open_answers ' + answer_status + ' ms-imgpos' + survey_aoptions[ oindex ][ 14 ] + ' msdateanswer" onclick="" data-unique="' + survey_aoptions[ oindex ][ 1 ] + '" data-qstep="' + qstepn + '" qid="' + played_question + '" id="survey_answer' + ( parseInt( key ) ) + '">' + answerimg + '<span><input' + qtooltip + ' type="text" maxlength="600" name="open_answer" class="open_text_answer date_answer" autocomplete="off" value="" placeholder="' + survey.questions[ played_question ][ key ].replace( /\[(.*)\]/, '' ) + '"></span></div>';
										survey.questions[ played_question ][ 'hasopen' ] = true;
									}
									else if ( survey_aoptions[ oindex ][ 0 ] == "numeric" ) {
										//print text input
										survey_content += '<div ' + tooltip + ' class="survey_element survey_answers ' + answer_ma_class + ' ' + survey_options[ 134 ] + ' survey_open_answers ' + answer_status + ' ms-imgpos' + survey_aoptions[ oindex ][ 14 ] + ' msnumericanswer" onclick="" data-unique="' + survey_aoptions[ oindex ][ 1 ] + '" data-qstep="' + qstepn + '" qid="' + played_question + '" id="survey_answer' + ( parseInt( key ) ) + '">' + answerimg + '<span><input' + qtooltip + ' type="text" autocomplete="off"  maxlength="600" name="open_answer" class="open_text_answer numeric_answer" value="" readonly="true" placeholder="' + survey.questions[ played_question ][ key ].replace( /\[(.*)\]/, '' ) + '"></span><div class="numeric_answer_slider" data-min="' + survey_aoptions[ oindex ][ 18 ] + '" data-max="' + survey_aoptions[ oindex ][ 19 ] + '" data-step="' + survey_aoptions[ oindex ][ 20 ] + '"></div></div>';
										survey.questions[ played_question ][ 'hasopen' ] = true;
									}
									else if ( survey_aoptions[ oindex ][ 0 ] == "select" ) {
										//print text input
										survey_content += '<div ' + tooltip + ' class="survey_element survey_answers ' + answer_ma_class + ' ' + survey_options[ 134 ] + ' survey_open_answers ' + answer_status + ' ms-imgpos' + survey_aoptions[ oindex ][ 14 ] + ' msselectanswer" onclick="" data-unique="' + survey_aoptions[ oindex ][ 1 ] + '" data-qstep="' + qstepn + '" qid="' + played_question + '" id="survey_answer' + ( parseInt( key ) ) + '">' + answerimg + '<span><select' + qtooltip + ' name="open_answer" class="open_text_answer select_answer">';
										survey_content += "<option value=''>" + survey.questions[ played_question ][ key ].replace( /\[(.*)\]/, '' ) + "</option>";
										if ( typeof survey_aoptions[ oindex ][ 17 ] != "undefined" ) {
											selectoptions = survey_aoptions[ oindex ][ 17 ].split(",");
											if ( typeof selectoptions != "undefined" ) {
												jQuery.each( selectoptions, function( index, value ) {
													if ( jQuery.trim( value ) != "" ) {
														survey_content += "<option value='" + jQuery.trim( value ) + "'>" + jQuery.trim( value ) + "</option>";
													}											
												});
											}
										}

										survey_content += '</select></span></div>';
										survey.questions[ played_question ][ 'hasopen' ] = true;
									}
									else {
									//generating default answer
										if ( survey_aoptions[ oindex ][ 13 ] == "1" ) {
											msanswerclass = "ms-a-hidelabel";
										}
										else {
											msanswerclass = "ms-a-label";											
										}
										survey_content += '<div class="survey_element default_answer survey_answers ' + answer_ma_class + ' ' + survey_options[ 134 ] + ' ' + answer_status + ' ms-imgpos' + survey_aoptions[ oindex ][ 14 ] + '" qid="' + played_question + '" data-qstep="' + qstepn + '" onclick="" id="survey_answer' + ( parseInt( key ) ) + '" ' + tooltip + '>' + answerimg + '<span class="' + msanswerclass + atextspanclass + '">' + survey.questions[ played_question ][ key ].replace( /\[(.*)\]/, '' ) + '</span></div>';
									}
							}
							else {
							//processing rating questions
								if ( tooltip == "" ) {
									if ( survey.questions[ played_question ][ key ].replace( /\[(.*)\]/, '' ) == "" ) {
										tooltip = '';
									}
									else {
										tooltip = ' data-tooltip="' + survey.questions[ played_question ][ key ].replace( /\[(.*)\]/, '' ) + '"';
									}
								}
								if ( survey.rating_icon.indexOf( 'number' ) >= 0 ) {
									pansnum = key;
								}
								else {
									pansnum = "";									
								}
								answerimg = '<span ' + tooltip + '><div class="survey_rating survey_answers" onclick="" qid="' + played_question + '" data-qstep="' + qstepn + '" id="survey_answer' + ( parseInt( key ) ) + '">' + pansnum + '</div></span>'
								if ( counter == 1 ) {
									survey_content += '<div class="survey_element survey_answers ms_rating_question" onclick="" qid="' + played_question + '" data-qstep="' + qstepn + '">';
								}
								survey_content += answerimg;			
								if ( counter == answers_number ) {
									survey_content += '</div>';												
								}
							}
							if ( ( counter == separator ) && ( survey_qoptions[ played_question ][ 3 ] != 1 ) ) {
								counter = 0;
							}
						}
					};
							survey_content += dividequestions_end;
					//choices
					if ( survey_qoptions[ played_question ][ 0 ] == 'undefined' || survey_qoptions[ played_question ][ 0 ] == '' || survey_qoptions[ played_question ][ 0 ] == '0' ) {
						survey_qoptions[ played_question ][ 0 ] = 1;
					}
					//minchoices
					if ( survey_qoptions[ played_question ][ 1 ] == 'undefined' || survey_qoptions[ played_question ][ 1 ] == '' || survey_qoptions[ played_question ][ 1 ] == '0' ) {
						survey_qoptions[ played_question ][ 1 ] = 1;
					}
					if ( survey_qoptions[ played_question ][ 3 ] == 1 ) {
						survey_qoptions[ played_question ][ 0 ] = 1;						
						survey_qoptions[ played_question ][ 1 ] = 1;
					}
					if ( survey_options[ 151 ] == undefined ) {
						survey_options[ 151 ] = "";
					}
					if ( survey_qoptions[ played_question ][ 0 ] > 1 || survey.questions[ played_question ][ 'hasopen' ] == true || survey_options[ 152 ] == 1 && survey_qoptions[ played_question ][ 3 ] != '1' ) {
						if ( survey_options[ 151 ] == '2' ) {
							next_button = '<div class="ms-next-question survey_element survey_answers survey_answer_choice nextstyle' + survey_options[ 151 ] + ' ' + answer_ma_class + ' button button-secondary button-default passive" qid="' + played_question + '" data-qstep="' + qstepn + '" onclick="" id="surveychoice' + survey.id + '_' + played_question + '"><span class="ms-span-text"> ' + survey.languages.next_button + ' </span></div>';
						}
						else {
							next_button = '<div class="survey_element survey_answers survey_answer_choice nextstyle' + survey_options[ 151 ] + ' ' + answer_ma_class + '" qid="' + played_question + '" data-qstep="' + qstepn + '" onclick="" id="surveychoice' + survey.id + '_' + played_question + '"> <img src="' + survey.plugin_url + '/templates/assets/img/' + themeprefix + 'next-passive.png"> </div>';
						}
					}
					if ( survey_options[ 154 ] == 1 && played_question > 0 && survey.last_answer != ""  ) {
						if ( survey_options[ 151 ] == '2' ) {
							back_button = '<div class="survey_element survey_answers survey_answer_choice_back backstyle' + survey_options[ 151 ] + ' ' + answer_ma_class + ' backbutton button button-secondary button-default" qid="' + played_question + '" onclick="" id="surveychoice' + survey.id + '_' + played_question + '"> ' + survey.languages.back_button + ' </div>';
						}
						else {
							back_button = '<div class="survey_element survey_answers survey_answer_choice_back backstyle' + survey_options[ 151 ] + ' ' + answer_ma_class + ' backbutton" qid="' + played_question + '" onclick="" id="surveychoice' + survey.id + '_' + played_question + '"> <img src="' + survey.plugin_url + '/templates/assets/img/' + themeprefix + 'back.png"> </div>';
						}
					}
				}
				else {
					display = " ";
					if ( survey.survey_conds != "" && ( endstep == played_question ) ) {
						jQuery.map( survey.survey_conds, function( val, i ) {
							process_condition( val, i );
						});
					}
					if ( survey.social[ 3 ] == "endcontent" ) {
						display += socialbuttons;
					}
					if ( ( ( survey_options[ 125 ] == 1 && survey.form != 'false' ) || survey.form == 'true' ) && ( survey_options[ 126 ] == 1 || survey_options[ 127 ] == 1 ) && ( pformstep == played_question ) ) {
						survey_participant_form = '<div class="ms-participant-form">';
						jQuery.each( survey.customfields, function( index, value ) {
							if ( value.type == "html" && value.position == "1" ) {
								survey_participant_form += '<div class="ms-custom-field ms-custom-html-field">' + value.name + '</div>';
							}							
						})						
						survey_participant_form += '<p>' + survey.languages.pform_description + '</p>';
						if ( survey_options[ 126 ] == 1 ) {
							survey_participant_form += '<input type="text" class="ms-form-name" placeholder="' + survey.languages.name_placeholder + '" value="' +  survey.user.name + '">';
						}
						if ( survey_options[ 127 ] == 1 ) {
							survey_participant_form += '<input type="text" class="ms-form-email" placeholder="' + survey.languages.email_placeholder + '" value="' +  survey.user.email + '">';
						}	
						if ( survey.customfields != '' ) {
							jQuery.each( survey.customfields, function( index, value ) {
								if ( value.type == undefined || value.type == "text" ) {
									survey_participant_form += '<div class="ms-custom-field ms-custom-text-field"><input type="text" value="" name="' + value.id + '" class="' + value.id + ' customfields" placeholder="' + value.name + '"></div>';
								}
								if ( value.type == "select" ) {
									survey_participant_form += '<div class="ms-custom-field ms-custom-select-field"><select name="' + value.id + '" class="' + value.id + ' customfields">';
									jQuery.each( value.name.split( "," ), function( rindex, rvalue ) {
										subelement = rvalue.split( ":" );
										if ( subelement[ 1 ] == undefined ) subelement[ 1 ] = "";
										survey_participant_form += '<option value="' + subelement[ 1 ] + '">' + subelement[ 0 ] + '</option>';
									});
									survey_participant_form += '</select></div>';
								}
								if ( value.type == "textarea" ) {
									survey_participant_form += '<div class="ms-custom-field ms-custom-textarea-field"><textarea name="' + value.id + '" class="' + value.id + ' customfields" placeholder="' + value.name + '"></textarea></div>';
								}
								if ( value.type == "hidden" ) {
									survey_participant_form += '<div class="dnone ms-custom-field ms-custom-hidden-field"><input type="hidden" name="' + value.id + '" value="' + value.name + '" class="' + value.id + ' customfields"></div>';
								}
								if ( value.type == "radio" ) {
									survey_participant_form += '<div class="ms-custom-field ms-custom-radio-field">';
									jQuery.each( value.name.split( "," ), function( rindex, rvalue ) {
										subelement = rvalue.split( ":" );
										uniquenumber = Math.floor((Math.random() * 10000) + 1);
										survey_participant_form += '<div class="one-ms-custom-radio-field"><input type="radio" id="customfields-radio-' + value.id + '-' + uniquenumber + '" value="' + subelement[ 1 ] + '" name="' + value.id + '" class="' + value.id + ' customfields ms-custom-radiobox"><label for="customfields-radio-' + value.id + '-' + uniquenumber + '">' + subelement[ 0 ] + '</label></div>';
									});
								}
								if ( value.type == "checkbox" ) {
									chstatus = "";										
									chstatusexp = value.name.match(/\[(.*)\]/);
									if ( chstatusexp != null ) {
										if ( chstatusexp[ 1 ].toLowerCase() == "checked" ) {
											chstatus = "checked";
										}
									}
									uniquenumber = Math.floor((Math.random() * 10000) + 1);
									survey_participant_form += '<div class="ms-custom-field ms-custom-checkbox-field"><input type="checkbox" id="customfields-radio-' + value.id + '-' + uniquenumber + '" ' + chstatus + ' value="' + survey.languages.checkboxvalue + '" name="' + value.id + '" class="' + value.id + ' customfields ms-custom-checkbox"><label for="customfields-radio-' + value.id + '-' + uniquenumber + '">' + value.name.replace(/\[(.*?)\]/g, "") + '</label></div>';
								}
								if ( value.type == "html" && value.position == "2" ) {
									survey_participant_form += '<div class="ms-custom-field ms-custom-html-field">' + value.name + '</div>';
								}
							});
						}
						if ( survey_options[ 160 ] == "1" ) {
							survey_participant_form += '<div class="participant-form-confirmation"><input type="checkbox" id="mspform_confirmation" name="mspform_confirmation" class="mspform_confirmation" checked value="1" /><label for="mspform_confirmation"> ' + survey.languages.mailconfirmation + '</label></div>';	
						}
						survey_participant_form += '<div class="send-participant-form-container"><a href="#" onclick="return false;" class="send-participant-form button button-secondary button-default">' + survey.languages.send_button + '</a></div>';
						jQuery.each( survey.customfields, function( index, value ) {
							if ( value.type == "html" && value.position == "3" ) {
								survey_participant_form += '<div class="ms-custom-field ms-custom-html-field">' + value.name + '</div>';
							}							
						})
					}
					if ( endstep == played_question ) {
						msparams = [ 'ms-cqn-' + survey.survey_id, "", -1, 'days' ];
						setCookie( msparams );
						if ( survey_options[ 12 ].indexOf( "[noclose]" ) > 0 ) {
							survey_options[ 12 ]  = survey_options[ 12 ].replace( "[noclose]", "" );
							noclose = "true";
						}
						if ( display.indexOf( "[noclose]" ) > 0 ) {
							display  = display.replace( "[noclose]", "" );
							noclose = "true";
						}
						survey.timeup = '';
						if ( survey.qtcurrent < 1 && survey.quiztimer > 0 ) {
							survey.timeup = survey.languages.timeisup;
						}
						if ( survey_options[ 12 ] != "" ) {
							var savgscore = Math.round( (surveyscore / played_question ) * 100) / 100;
							if ( survey_options[ 163 ] == '1' && admin_comment != "" && admin_comment != null ) {
								admin_comment_container = "<div class='admin-comment-container'>" + admin_comment + "</div>";
							}
							else {
								admin_comment_container = "";
							}
							survey_content += '<div class="survey_table endcontent">';
							if ( survey.preview == "true" ) {
								survey_content += remove_image;
							}
							survey_content += '<div class="survey_element survey_endcontent" ' + qtooltip + '><span>' + admin_comment_container + '<p>' + survey.timeup + '</p><p>' + survey_options[ 12 ].replace( '[avgscore]', savgscore ).replace( '[score]', surveyscore ).replace( '[correct]', surveycorrect ).replace(/[|]/gi, "'" ) + '</p>' + display + '</span></div></div>';
						}
					}
					if ( ( pformstep == played_question ) && ( survey_participant_form != "" ) ) {
						abortTimer();
						if ( survey.confirmation == "true" && survey.confirmed == "false" ) {
							survey_content += '<div class="modal-survey-confirmation-page"><div class="survey_table ' + tableclass + '"><div class="header">' + survey.languages.confirm_title + '</div><div class="confirm-ms-form-container"></div></div></div></div>';
						}
							survey_content += '<div class="survey_table ' + tableclass + ' part-form-cont"><div class="survey_element survey_form" ' + qtooltip + '>' + survey_participant_form + '</div></div>';
					}
					if ( survey_content == "" ) {
						survey_content += '<div class="survey_element survey_question" style="display:none;"></div>'
					}
				}
				if ( jQuery( '#' + survey.survey_id + ' #question_' + played_question + ' .answer' ).length < 3 ) {
					survey_content += '</div>';
				}
				if ( ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) && survey.social[ 3 ] == "bottom" ) {
					survey.social[ 3 ] = "end";
				}
				if ( survey.social[ 3 ] == "bottom" || ( survey.social[ 3 ] == "end" && played_question == survey.questions.length ) ) {
					survey_content += socialbuttons;
				}
				if ( ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) && survey_participant_form == "" && endstep == played_question + 1 ) {					
					if ( survey.confirmation == "true" && survey.confirmed == "false" ) {
						survey_content += '<div class="modal-survey-confirmation-page"><div class="survey_table ' + tableclass + '"><div class="header">' + survey.languages.confirm_title + '</div><div class="confirm-ms-form-container"></div></div></div></div>';
					}
						survey_content += '<div class="survey_table ' + tableclass + ' pform-row "><div class="send-participant-form-container"><a href="#" onclick="return false;" class="send-participant-form button button-secondary button-default">' + survey.languages.send_button + '</a></div></div></div>';
				}
				if ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) {
					survey_progress_bar = "";
					if ( ( played_question == 1 ) && ( jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) ) {
						modal_survey.css( "visibility", "hidden" );
					}
				}
				if ( typeof survey.progressbar != 'undefined' ) {
					if ( survey.progressbar == "top" ) {
						modal_survey.html( survey_progress_bar + survey_content );
					}
					else {
						modal_survey.html( survey_content + survey_progress_bar );						
					}
				}
				else {
					modal_survey.html( survey_content + survey_progress_bar );
				}
				initialize_special_answers();
				if ( survey.qtcurrent > 0 && survey_options[ 156 ] > 0 ) {
					survey.qtid = setTimeout( quizcountdown, 1000);
				}
				if ( ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) && survey_participant_form == "" && endstep == played_question + 1 ) {
					jQuery( "body" ).on( "click", "#survey-" + survey.survey_id + "-" + unique_key + " .send-participant-form", function( e ) {
						e.preventDefault();
						if ( send_bulk_answers() == true ) {
							rmdni = false;
							played_question++;
							continue_survey();
							return;
						}
					})
				}
				if ( endstep == played_question ) {
					if ( soctitle == "" && socdesc == "" && socimg == "" && survey.social[ 0 ] != 0 ) {
						survey.social[ 6 ] = survey.social[ 6 ].replace( '{score}', surveyscore ).replace( '{correct}', surveycorrect ).replace( '[score]', surveyscore ).replace( '[correct]', surveycorrect );
						survey.social[ 7 ] = survey.social[ 7 ].replace( '{score}', surveyscore ).replace( '{correct}', surveycorrect ).replace( '[score]', surveyscore ).replace( '[correct]', surveycorrect );
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .share-twitter" ).attr( "href", "http://twitter.com/share?url=" + survey.social[ 4 ] + "&text=" + survey.social[ 6 ] );
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .share-pinterest" ).attr( "href", "http://pinterest.com/pin/create/bookmarklet/?url=" + survey.social[ 4 ] + "&media=" + survey.social[ 5 ] + "&description=" + survey.social[ 6 ] );
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .share-linkedin" ).attr( "href", "https://www.linkedin.com/shareArticle?mini=true&url=" + survey.social[ 4 ] + "&title=" + survey.social[ 6 ] + "&summary=" + survey.social[ 7 ] );
						if ( survey.social[ 8 ] != "" ) {
							jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .share-facebook" ).attr( "href", "https://www.facebook.com/v2.9/dialog/share?app_id=" + survey.social[ 8 ] + "&display=popup&name=" + survey.social[ 6 ] + "&quote=" +  survey.social[ 6 ] + "&caption=" + survey.social[ 7 ] + "&href=" + survey.social[ 4 ] + "?msid=" + window.btoa( survey.social[ 5 ] ).replace(/=/g, '%3D') + "&fburl=" + survey.social[ 4 ] + "?msid=" + window.btoa( survey.social[ 5 ] ).replace(/=/g, '%3D') + "&link=" + survey.social[ 4 ].replace(/=/g, '%3D') + "?msid=" + window.btoa( survey.social[ 5 ] ).replace(/=/g, '%3D') + "&version=v2.9" );
						}
					}
					else {
						soctitle = soctitle.replace( '{score}', surveyscore ).replace( '{correct}', surveycorrect ).replace( '[score]', surveyscore ).replace( '[correct]', surveycorrect );
						socdesc = socdesc.replace( '{score}', surveyscore ).replace( '{correct}', surveycorrect ).replace( '[score]', surveyscore ).replace( '[correct]', surveycorrect );
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .share-twitter" ).attr( "href", "http://twitter.com/share?url=" + survey.social[ 4 ] + "&text=" + soctitle );
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .share-pinterest" ).attr( "href", "http://pinterest.com/pin/create/bookmarklet/?url=" + survey.social[ 4 ] + "&media=" + socimg + "&description=" + soctitle );
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .share-linkedin" ).attr( "href", "https://www.linkedin.com/shareArticle?mini=true&url=" + survey.social[ 4 ] + "&title=" + soctitle + "&summary=" + survey.social[ 7 ] );
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .share-facebook" ).attr( "href", "https://www.facebook.com/v2.9/dialog/share?app_id=" + survey.social[ 8 ] + "&display=popup&name=" + soctitle + "&quote=" + soctitle + "&caption=" + socdesc + "&href=" + survey.social[ 4 ] + "?msid=" + window.btoa( socimg ).replace(/=/g, '%3D') + "&fburl=" + survey.social[ 4 ] + "?msid=" + window.btoa( socimg ).replace(/=/g, '%3D') + "&link=" + survey.social[ 4 ] + "?msid=" + window.btoa( socimg ).replace(/=/g, '%3D') + "&version=v2.9" );		
					}
					var data = {
						action: 'ajax_survey_answer',
						sspcmd: 'displaychart',
						sid: survey.survey_id,
						postid: survey.postid
						};
						jQuery.post( survey.admin_url, data, function( response ) {
							if ( response != "" ) {
								var newElement = document.createElement('div');
								newElement.innerHTML = response;
								var arr = newElement.getElementsByTagName('script')
								for (var n = 0; n < arr.length; n++) {
									eval( arr[ n ].innerHTML );
								}
								var resp = response.split( "|endcontent-params|" );
								var response_stripped = stripScripts( resp[ 0 ] );
								modal_survey.find( ".endcontent .survey_element" ).append( response_stripped );
								if (  resp[ 1 ] == undefined && jQuery( ".modalsurvey-progress-circle" ).length > 0 ) {
									jQuery( "#survey-" + survey.survey_id + "-" + unique_key ).css( "display", "block" );
									jQuery( ".modalsurvey-progress-circle" ).circliful();
								}
								else {
									if ( resp[ 1 ] != undefined ) {
										var cfg = JSON.parse( resp[ 1 ] );
										setTimeout( function() {
											jQuery( "#survey-results-" + survey.survey_id + "-endcontent" ).pmsresults({ 
												"style": cfg.style, "datas": cfg.datas 
											});
										}, 0 );
									}
								}
							}
						})
					//scrollto endcontent if exists
					if ( modal_survey.find( ".survey_endcontent" ).length > 0 ) {
						if ( detectmob() && survey.scrollto != 'false' ) {
							jQuery( "html, body" ).animate({
								scrollTop: modal_survey.find( ".survey_endcontent" ).offset().top - ( ( jQuery( window ).height() / 2 ) - ( modal_survey.find( ".survey_endcontent" ).height() / 2 ) )
							}, 500 );
						}
					}
				}
				if ( survey.style == "flat" ) {
					var contwidth = modal_survey.parent().width();
				}
				else {
					var contwidth = modal_survey.width();
				}
				jQuery( "#survey-results-" + random + ", #survey-results-" + random + " div"  ).css({ "width": parseInt( contwidth * 0.75 )+"px", height: parseInt( jQuery( window ).height() * 0.5 ) + "px" })
				if ( back_button != "" ) {
					if ( survey_options[ 151 ] == "1" || survey_options[ 151 ] == "2" ) {
						if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .social-sharing" ).length > 0 ) {
							jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .social-sharing" ).before( back_button );							
						}
						else {
							jQuery( "#survey-" + survey.survey_id + "-" + unique_key ).append( back_button );
						}
					}
					else {
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_question" ).append( back_button );
					}					
				}
				if ( next_button != "" && survey.display != 'all' && ! jQuery.isNumeric( survey.display ) && ( survey.display.indexOf( "," ) < 0 ) ) {
					if ( survey_options[ 151 ] == "1" || survey_options[ 151 ] == "2" ) {
						if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .social-sharing" ).length > 0 ) {
							jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .social-sharing" ).before( next_button );							
						}
						else {
							jQuery( "#survey-" + survey.survey_id + "-" + unique_key ).append( next_button );
						}
					}
					else {
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_question" ).append( next_button );
					}
				}
				//make the style
				set_survey_style();
				//make animation
				make_animation();
				if ( survey_options[ 20 ] == "1" && played_question > 0 && ( survey.display == '' || survey.display == '1' ) ) {
					setTimeout( function() {
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .progress" ).css( "width", percent + "%" );
						countTo( modal_survey.find( ".progress_counter" ), parseInt( percent_last ), parseInt( percent ), 500, 50, "%", 0, null, null );
					}, parseInt( survey_options[ 11 ] ) );
				}
			}
			if ( ( ( survey.questions.length - 1 ) < played_question ) || ( survey.expired == 'true' ) || ( played_question == endstep && ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) ) ) {
				played++;
				if ( survey.style == 'click' ) {
					if ( survey.expired == 'true' ) {
						jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_question" ).html( '<span>' + survey.message.replace( '[score]', surveyscore ).replace( '[correct]', surveycorrect ).replace( /[|]/gi, "'" ) + '</span>' );
					}
				}
				if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_element" ).length > 0 ) {
					if ( ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .survey_element" ).html().indexOf( "<a " ) >= 0 || noclose == "true" || survey_options[ 23 ] == 0 ) || ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) ) {
						jQuery( "body" ).on( "click", "#survey-" + survey.survey_id + "-" + unique_key + " .survey_element a", function() {
							if ( jQuery( this ).hasClass( "send-participant-form" ) ) {
								if ( rmdni == false ) {
									sendbutton = jQuery( this ).parent();
									rmdni = true;
									senderror = false;
									if ( survey_options[ 125 ] == 1 && ( survey_options[ 126 ] == 1 || survey_options[ 127 ] == 1 ) ) {
										if ( survey_options[ 126 ] == 1 ) {
											if ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-name" ).val().length < 3 ) {
												inputtemp = jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-name" ).val();
												jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-name" ).css( "color", "#FC0303" );
												jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-name" ).val( survey.languages.shortname );
												setTimeout( function() {
													jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-name" ).css( "color", "" );
													jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-name" ).val( inputtemp );
													rmdni = false;
												}, 2000);
												senderror = true;
											}
										}
										if ( survey_options[ 127 ] == 1 ) {
											if ( ! isValidEmailAddress( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-email" ).val() ) && survey_options[ 146 ] == 1 ) {
												inputtemp = jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-email" ).val();
												jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-email" ).css( "color", "#FC0303" );
												jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-email" ).val( survey.languages.invalidemail );
												setTimeout( function() {
													jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-email" ).css( "color", "" );
													jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-email" ).val( inputtemp );
													rmdni = false;
												}, 2000 );
												senderror = true;
											}
										}						
										
							if ( survey.customfields != '' ) {
								customfieldsarray = [];
								jQuery.each( survey.customfields, function( index, value ) {
									fieldname = value.id;
									thisdata = new Object;
									if ( value.type == undefined ) value.type = "text";
									if ( value.type == "radio" || value.type == "select" ) {
										value.minlength = 0;
									}
									if ( value.type == "radio" ) {
										warningclass = "warning-icon2";
									}
									else {
										warningclass = "warning-icon";										
									}
									if ( ( value.required == true || value.required == "true" ) && ( ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " ." + value.id ).val() == '' || jQuery( "." + value.id ).val().length < value.minlength ) || ( value.type == 'radio' && jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " ." + value.id + ":checked" ).val() == undefined ) || ( value.type == 'checkbox' && jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " ." + value.id + ":checked" ).val() == undefined ) ) ) {
										thisval = jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id ).val();
										jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id ).css( "color", "#FC0303" );
										jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id ).parent().addClass( warningclass );
										jQuery( "html, body" ).animate({
											scrollTop: jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id ).offset().top - ( ( jQuery( window ).height() / 2 ) - ( jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id ).height() / 2 ) )
										}, 500 );
										if ( value.type == "text" || value.type == "textarea" ) {
											jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id ).val( value.warning );
										}
										setTimeout( function() {
											jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id).css( "color", "" );
											if ( value.type == "text" || value.type == "textarea" ) {
												jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id ).val( thisval );
											}
											jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id ).parent().removeClass( warningclass );
											jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .subscribe').css({
												"opacity": "1",
												"cursor": "pointer"
											});
											rmdni = false;
										}, 3000);
										senderror = true;
										return false;
									}
									else {
										if ( value.type == "radio" ) {
											thisdata[ fieldname ] = jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id + ':checked' ).val();
										}
										else if ( value.type == "checkbox" && ! jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " ." + value.id ).is( ':checked' ) ) {
											thisdata[ fieldname ] = survey.languages.checkboxoffvalue;
										}
										else {
											thisdata[ fieldname ] = jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .' + value.id ).val();
										}
										if ( thisdata[ fieldname ] == "undefined" ) {
											thisdata[ fieldname ] = "empty";
										}
										if ( value.type != 'html' ) {
											customdatas = jQuery.extend( {}, customdatas, thisdata); 
											customfieldsarray.push( value.id );
											//senderror = false;
										}
									}
								});
							}
									if ( survey_options[ 160 ] == "1" && survey_options[ 161 ] != "1" && ! jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .participant-form-confirmation .mspform_confirmation' ).prop( "checked" ) ) {
										warningclass = "warning-icon2";										
										jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .participant-form-confirmation' ).addClass( warningclass );	setTimeout( function() {
											jQuery( "#survey-" + survey.survey_id + "-" + unique_key + ' .participant-form-confirmation' ).removeClass( warningclass );
											rmdni = false;
										}, 2000);
										senderror = true;
									}

							
									}
									if ( ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) && senderror == false ) {
										sba = send_bulk_answers();
										bulktimer = 3000;
										if (  sba != true ) {
											rmdni = false;
											return false;
										}
									}
									if ( senderror == false ) {
										var data = {
												action: 'ajax_survey_answer',
												sspcmd: 'form',
												pform: pform,
												pform_pos: survey.pform,
												endcontent: true,
												conf: jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .mspform_confirmation" ).prop( "checked" ),
												name: jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-name" ).val(),
												email: jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-form-email" ).val(),
												sid: survey.survey_id,
												postid: survey.postid,
												cae: survey.cae
											};
										customdatas[ 'customfieldsarray' ] = customfieldsarray;
										data = jQuery.extend( {}, data, customdatas );
										sendbutton.html('<img class="form-preloader" src="' + survey.plugin_url + '/templates/assets/img/' + survey_options[ 133 ] + '">');
									setTimeout( function() {
										jQuery.post( survey.admin_url, data, function( response ) {
											if ( response.toLowerCase().indexOf( "success" ) >= 0 ) {
												sendbutton.html( survey.languages.success );
												pform = true;
												if ( survey.pform == "start" ) {
													played_question = -1;
												}
												played_question++;
												continue_survey();
												/*setTimeout( function() {
													if ( ! jQuery( this ).hasClass( "ms-social-share" ) ) {
														close_survey( jQuery( this ) );
													}
												}, 1000 );*/
											}
											else {
												sendbutton.html( survey.languages.campaignerror );
											}
											rmdni = false;
										})
									}, bulktimer );
										//if ( survey.display == "all" || jQuery.isNumeric( survey.display ) ) {
										if ( survey_options[ 19 ] != "" ) {
											setTimeout( function() {
													if ( isUrlValid( survey_options[ 19 ] ) && ( endstep == played_question ) ) {
														window.location.href = survey_options[ 19 ];
													}
											}, parseInt( survey_options[ 23 ] ) );
										}
										//}
									}
								}
								else {
									if ( ! jQuery( this ).hasClass( "ms-social-share" ) && senderror != true ) {
										close_survey();
									}
								}
							}
						})
						if ( survey.display == "all" || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) && ! jQuery( this ).hasClass( "send-participant-form" ) ) {
							if ( survey_options[ 19 ] != "" ) {
								if ( isUrlValid( survey_options[ 19 ] ) && ( endstep == played_question ) ) {
									setTimeout( function() {
												window.location.href = survey_options[ 19 ];
									}, parseInt( survey_options[ 23 ] ) );
								}
							}
						}
					}
					else {
						setTimeout( function() {
						if ( survey.social[ 0 ] != 'on' && survey_options[ 136 ] != 1 ) {
							if ( survey.style == 'flat' ) {
								if ( survey_options[ 158 ] == "fade" ) {
									modal_survey.fadeOut( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
										modal_survey.css( "visibility", "hidden" );
									} );
								}
								else {
									modal_survey.slideUp( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
										modal_survey.css( "visibility", "hidden" );										
									} );
								}
							}
							else {
								if ( survey_options[ 158 ] == "fade" ) {
										modal_survey.fadeOut( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
										modal_survey.css( "visibility", "hidden" );										
									} );
								}								
								else {
									if ( survey_options[ 0 ] == "bottom" ) {
										modal_survey.animate({ bottom: "-" + parseInt( modal_survey.height() + 100 ) + "px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
											modal_survey.css( "bottom", "-" + parseInt( modal_survey.height() + 100 ) + "px");
											modal_survey.css( "display", "table" );
											modal_survey.css( "visibility", "hidden" );										
											});
									}
									if ( survey_options[ 0 ] == "center" ) {
										if ( survey.align == "left" ) {
											modal_survey.animate({ left: "-5000px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function(){
												modal_survey.css( "bottom", "-" + parseInt( modal_survey.height() + 100) + "px");
												modal_survey.css( "display", "table" );
												modal_survey.css( "visibility", "hidden" );										
											});
										}
										else {
											modal_survey.animate({ right: "-5000px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
												modal_survey.css( "bottom", "-" + parseInt( modal_survey.height() + 100 ) + "px" );
												modal_survey.css( "display", "table" );
											})							
										}
									}
									if ( survey_options[ 0 ] == "top" ) {
										modal_survey.animate({ top: "-" + parseInt( modal_survey.height() + 100 ) + "px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function(){
											modal_survey.css( "bottom", "-" + parseInt( modal_survey.height() + 100) + "px");
											modal_survey.css( "display", "table" );
											modal_survey.css( "visibility", "hidden" );										
										})
									}
								}
								if ( ( jQuery( "#bglock" ).length > 0 ) && ( jQuery( "#bglock" ).css( "display" ) == "block" ) && ( parseInt( survey_options[ 13 ] ) ) == 1 ) {
									jQuery( "#bglock" ).fadeOut( 1000, function() {
										jQuery( "#bglock" ).remove();
									});
								}
								played_question = -1;
							}
						}
							if ( survey_options[ 19 ] != "" ) {
								if ( isUrlValid( survey_options[ 19 ] ) && ( endstep == played_question ) ) {
									window.location.href = survey_options[ 19 ];
								}
							}
						}, parseInt( survey_options[ 23 ] ) );
					}
				}
			}

			if ( jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) && played_question == ( endstep - 1 ) ) {
				divide_questions( survey.display, endstep );
			}
			if ( survey.style == 'flat' && ( survey.display == 'all' || jQuery.isNumeric( survey.display ) || ( survey.display.indexOf( "," ) >= 0 ) ) ) {
				while ( endstep > parseInt( played_question + 1 ) ) {
					alldisplay_survey_content = survey_content;
					played_question++;
					play_survey();
				}
			}
		}
		
		function apply_condition( condition, index ) {
			
		}
		
		function process_condition( condition, index ) {
			var target = "", qscexp = "", condition_result = [], cond_result = 'false', condcounter = 0, newval = JSON.parse( condition ), cond_divider = "";
			if ( newval[ 0 ].indexOf( "||" ) > 0 ) {
				cond_divider = "||";
			}
			if ( newval[ 0 ].indexOf( "&&" ) > 0 ) {
				cond_divider = "&&";
			}
			if ( newval[ 0 ].indexOf( cond_divider ) > 0 ) {
				var expl_conds_list = newval[ 0 ].split( cond_divider );
			}
			else {
				var expl_conds_list = [
				newval[ 0 ]
				];
			}
			if ( newval[ 1 ].indexOf( cond_divider ) > 0 ) {
				var expl_conds_list2 = newval[ 1 ].split( cond_divider );
			}
			else {
				var expl_conds_list2 = [
				newval[ 1 ]
				];
			}
			if ( newval[ 2 ].indexOf( cond_divider ) > 0 ) {
				var expl_conds_list3 = newval[ 2 ].split( cond_divider );
			}
			else {
				var expl_conds_list3 = [
				newval[ 2 ]
				];
			}
			jQuery.each( expl_conds_list, function( i, nline ) {
				between = [];
				if ( nline == "score" ) {
					target = surveyscore;
				}
				if ( nline == "avgscore" ) {
					target = ( surveyscore / played_question ).toFixed( 2 );
				}
				if ( nline == "correct" ) {
					target = surveycorrect;
				}
				if ( nline == "time" && survey.qtcurrent <= 0 ) {
					expl_conds_list2[ i ] = "higher";
					target = 2;
					expl_conds_list3[ i ] = 1;
				}
				if ( nline.indexOf( "questionscore_" ) >= 0 ) {
					qscexp = nline.split("_");
					target = question_score[ qscexp[ 1 ] ];
				}
				if ( nline.indexOf( "questionanswer_" ) >= 0 ) {
					qscexp = nline.split("_");
					target = question_choice[ qscexp[ 1 ] ];
				}
				if ( nline.indexOf( "questioncatscore_" ) >= 0 ) {
					qscexp = nline.split("_");
					delete surveycatscore[ "-" ];
					target = surveycatscore[ qscexp[ 1 ] ];
					if ( expl_conds_list3[ i ] == "highest" ) {
						expl_conds_list3[ i ] = surveycatscore[ array_max( surveycatscore ) ];
					}
					if ( expl_conds_list3[ i ] == "lowest" ) {
						expl_conds_list3[ i ] = surveycatscore[ array_min( surveycatscore ) ];
					}
				}
				if ( expl_conds_list3[ i ] != undefined ) {
					if ( expl_conds_list3[ i ].toString().indexOf( "-" ) >= 0 ) {
						between = expl_conds_list3[ i ].toString().split( "-" );
					}
				}
				if ( between == undefined ) {
					between[ 0 ] = 0;
					between[ 1 ] = 0;
				}
				if ( expl_conds_list2[ i ] == "higher" && target > parseFloat( expl_conds_list3[ i ] ) ) {
					condition_result.push( 'true' );
				}
				else if ( expl_conds_list2[ i ] == "equal" && ( target == parseFloat( expl_conds_list3[ i ] ) || ( target >= parseFloat( between[ 0 ] ) && target <= parseFloat( between[ 1 ] ) ) ) ) {
					condition_result.push( 'true' );
				}
				else if ( expl_conds_list2[ i ] == "lower" && target < parseFloat( expl_conds_list3[ i ] ) ) {
					condition_result.push( 'true' );
				}
				else {
					condition_result.push( 'false' );
				}
			});
			jQuery.each( condition_result, function( i, cr ) {
				//check single condition
				if ( condition_result.length == 1 ) {
					if ( cr == 'true' ) {
						cond_result = 'true';
					}
				}
				else {
				//check advanced conditions
					if ( cond_divider == "||" ) {
						//check AND conditions
						if ( cr == 'true' ) {
							condcounter++;
						}
						if ( condition_result.length - 1 == i ) {
							if ( condition_result.length == condcounter ) {
								cond_result = 'true';
							}
						}
					}
					if ( cond_divider == "&&" ) {
						//check OR conditions
						if ( cr == 'true' ) {
							condcounter++;
						}
						if ( condition_result.length - 1 == i ) {
							if ( condcounter > 0 ) {
								cond_result = 'true';
							}
						}
					}
				}
			})
			if ( cond_result == 'true' ) {
				random = Math.floor( Math.random() * ( 1000000 - 1000 + 1 ) + 1000 );
				if ( newval[ 3 ] == "display" ) {
					if ( survey_options[ 162 ] == 1 ) {
						//display all messages with same scores
						display += "<p>" + newval[ 4 ].replace(/[|]/g, "'") + "</p>";
					}
					else {
						//display one message with same scores
						if ( condapplied == 0 ) {
							display = "<p>" + newval[ 4 ].replace(/[|]/g, "'") + "</p>";
						}
					}
					condapplied++;
				}
				if ( newval[ 3 ] == "soctitle" ) {
					soctitle = newval[ 4 ];
				}
				if ( newval[ 3 ] == "socdesc" ) {
					socdesc = newval[ 4 ];
				}
				if ( newval[ 3 ] == "socimg" ) {
					socimg = newval[ 4 ];
				}
				if ( newval[ 3 ] == "redirect" ) {
					setTimeout( function() {
						window.location.href = newval[ 4 ];
					}, parseInt( survey_options[ 23 ] ) );
				}
				if ( newval[ 3 ].indexOf( "questionscore" ) >= 0 ) {
					setTimeout( function() {
						window.location.href = newval[ 4 ];
					}, parseInt( survey_options[ 23 ] ) );
				}
				if ( newval[ 3 ] == "displayirchart" || newval[ 3 ] == "displayischart" || newval[ 3 ] == "displayicchart" ) {
					display += "<div id='survey-results-" + random + "' class='displaychart survey-results'><div class='modal-survey-chart0'><canvas style='width: 100%; height: 100%;'></canvas></div></div>";
					if ( ( newval[ 4 ] == "" ) && ( newval[ 3 ] == "displayischart" || newval[ 3 ] == "displayirchart" || newval[ 3 ] == "displayicchart" ) ) {
						chartstyleparams = 'style:radarchart';
					}
					if ( ( newval[ 4 ] != "" ) && ( newval[ 3 ] == "displayischart" || newval[ 3 ] == "displayirchart" || newval[ 3 ] == "displayicchart" ) ) {
						chartstyleparams = newval[ 4 ];
					}
					chartmode = newval[ 3 ];
				}				
			}
		}
		
		function stripScripts( s ) {
			var div = document.createElement( 'div' );
			div.innerHTML = s;
			var scripts = div.getElementsByTagName( 'script' );
			var i = scripts.length;
			while (i--) {
			  scripts[ i ].parentNode.removeChild( scripts[ i ] );
			}
			return div.innerHTML;
		}
		
		function divide_questions( qnums, max ) {
			var sections = Math.ceil( max / qnums );
			var sqnums = 0;
			var cqnum = 0;
			var back_button = "";
			if ( qnums.indexOf( "," ) >= 0 ) {
				sqnums = qnums.split( "," );
				sections = sqnums.length;
			}
			var current_node = 0;
			var current_section = played_section;
			var hide_next = "";
			var percent_last = 0;				
			if ( played_section == 1 ) {
				modal_survey.find( ".each-question" ).each( function( index ) {
					if ( current_node == index ) {
						jQuery( this ).addClass( "section" + current_section + " sections" );
						current_section++;
						if ( sqnums.length > 0 ) {
							cqnum = sqnums[ parseInt( current_section ) - 2 ];
							current_node = parseInt( index ) + parseInt( cqnum );
						}
						else {
							cqnum = qnums;							
							current_node = parseInt( index ) + parseInt( cqnum );
						}
					}
					else {
						jQuery( this ).addClass( "section" + parseInt( current_section - 1 ) + " sections" );
					}
				});
				// adding progress bar in case display!="all", but specified a number and it is turned ON
				if ( survey_options[ 20 ] == "1" && ( survey.qo.length >= parseInt( played_section ) ) && survey.display != "all" && survey.display != "" && editmode != "true" ) {
					survey_progress_bar = '<div class="survey-progress-bar"><div class="survey-progress-ln"><span class="progress_counter">0%</span></div><div class="survey-progress-ln2"><span class="progress"></span></div></div>';
					if ( modal_survey.find( ".survey-progress-bar" ).length > 0 ) {
						survey_progress_bar = "";
					}
					if ( typeof survey.progressbar != 'undefined' ) {
						if ( survey.progressbar == "top" ) {
							modal_survey.prepend( survey_progress_bar );
						}
						else {
							modal_survey.append( survey_progress_bar );						
						}
					}
					else {
						modal_survey.append( survey_progress_bar );
					}
				}
				else {
					survey_progress_bar = "";
				}
				if ( modal_survey.find( ".ms-next-question-container").length < 1 ) {
					back_button = '<a href="#" data-qnums="' + qnums + '" data-max="' + max + '" class="ms-next-back button button-secondary button-default">' + survey.languages.back_button + '</a>';
					modal_survey.append( '<div class="survey_table ms-next-question-container">' + back_button + '<a href="#" data-qnums="' + qnums + '" data-max="' + max + '" class="ms-next-question button button-secondary button-default"><span class="ms-span-text">' + survey.languages.next_button + '</span></a></div>' );
				}
				modal_survey.find( ".pform-row, .part-form-cont" ).css( "display", "none" );
				modal_survey.find( ".ms-next-back" ).css( "visibility", "hidden" );
			}
			modal_survey.find( ".sections" ).each( function( index ) {
				if ( ! jQuery( this ).hasClass( "section" + played_section ) ) {
						jQuery( this ).css( "display", "none" );	
				}
				else {
					if ( survey_options[ 158 ] == "fade" ) {
						jQuery( this ).fadeIn( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
							jQuery( this ).css( "display", "block" );	
						});
					}
					else {
							jQuery( this ).css( "display", "block" );	
					}
				}
			})
			if ( played_section >= sections ) {
				played_section = sections;
				//modal_survey.find( ".pform-row, .part-form-cont" ).fadeIn( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
					if ( survey.confirmation == "true" && survey.confirmed == "true" || ( survey.confirmation == "false" ) ) {
						modal_survey.find( ".ms-next-question" ).css( "display", "none" );
						modal_survey.find( ".modal-survey-confirmation-page" ).css( "display", "none" );
						modal_survey.find( ".pform-row, .part-form-cont" ).css( "display", "table" );
					}
					else if ( survey.confirmation == "true" && survey.confirmed == "false" ) {
						modal_survey.find( ".ms-next-question" ).css( "display", "none" );
						if ( editmode == "false" ) {
							show_confirmation_content();
							modal_survey.find( ".modal-survey-confirmation-page" ).css( "display", "table" );
						}
						else {
							modal_survey.find( ".ms-next-question" ).css( "display", "block" );							
						}
						modal_survey.find( ".pform-row, .part-form-cont" ).css( "display", "none" );
					}
				//} );
			}
			else {
				modal_survey.find( ".ms-next-question-container" ).css( "display", "flex" );
				modal_survey.find( ".pform-row, .part-form-cont" ).css( "display", "none" );
				modal_survey.find( ".ms-next-question" ).css( "display", "inline-block" );
			}
			//display next button in embed mode with display="1" when no open answers and only one answer required/optional
			//survey_qoptions = survey.qo;
			if ( typeof survey_qoptions[ played_section - 1 ] != "undefined" ) {
				if ( survey_options[ 152 ] != 1 && survey.display == "1" && modal_survey.find( ".section" + ( played_section ) + " .survey_open_answers" ).length < 1 && parseInt( survey_qoptions[ played_section - 1 ][ 0 ] ) < 2 && parseInt( survey_qoptions[ played_section - 1 ][ 1 ] ) < 2 ) {
					modal_survey.find( ".ms-next-question" ).css( "display", "none" );
				}
				else {
					if ( modal_survey.find( ".pform-row, .part-form-cont" ).css( "display" ) != "table" ) {
						modal_survey.find( ".ms-next-question" ).css( "display", "inline-block" );
					}
				}
			}
			if ( survey_options[ 20 ] == "1" && ( survey.qo.length >= parseInt( played_section ) ) && survey.display != "all" && survey.display != "" ) {
				percent = Math.ceil( ( parseInt( played_section ) / survey.qo.length ) * 100 );
				if ( parseInt( played_section ) > 1 ) {
					percent_last = Math.ceil( ( parseInt( played_section - 1 ) / parseInt( survey.qo.length ) ) * 100 );
				}
				if ( jQuery.isNumeric( survey.display ) && survey.display > 1 ) {				
					if ( played_section == 1 ) {
						percent_last = 0;
					}
					else if ( parseInt( played_section - 1 ) == parseInt( endstep / survey.display ) ) {
						percent_last = 100;
					}
					else {
						percent_last = parseInt( played_section * ( 100 / ( endstep / survey.display ) ) );
					}
				}
				modal_survey.find( ".survey-progress-bar .progress" ).css( "width", percent_last + "%" );
				modal_survey.find( ".survey-progress-bar .progress_counter" ).html( percent_last + "%" );
			}
			else {
				survey_progress_bar = "";
			}
			if ( played_section > 1 ) {
				if ( survey_options[ 154 ] == 1 ) {
					modal_survey.find( ".ms-next-back" ).css( "visibility", "visible" );
				}
			}
			if ( played_section == 1 ) {
				setTimeout( function() {
					modal_survey.css( "visibility", "visible" );				
				}, 10 );
			}
		}
		
		function show_confirmation_content() {
			var qtexts = "", cans = "", thsansmultiple = 0;
			editmode = "false";
			qtexts += "<div class='each-row'><div class='question-text-header'>" + survey.languages.confirm_question + "</div><div class='answer-text-header'>" + survey.languages.confirm_answer + "</div></div>";
			jQuery.each( survey.questions, function( index, value ) {
				cans = ""; thsansmultiple = 0; //clear the variables again in the inner cycle
				modal_survey.find( ".survey_answers" ).each( function( index2 ) {
					if ( ( jQuery( this ).hasClass( survey_options[ 134 ] + "selected" ) >= 1 || jQuery( this ).hasClass( "selected" ) ) && ( jQuery( this ).attr( "qid" ) == index ) && ( ! jQuery( this ).hasClass( "ms_rating_question" ) ) ) {
						if ( thsansmultiple > 0 ) {
							cans += ", ";							
						}
						if ( jQuery( this ).hasClass( "survey_open_answers" ) ) {
							cans += jQuery( this ).find( ".open_text_answer" ).val();
						}
						else {
							cans += survey.questions[ index ][ parseInt( jQuery( this ).attr( "id" ).replace( "survey_answer", "" ) ) ];
						}
						thsansmultiple++;
					}
				})
				qtexts += "<div class='each-row'><div class='question-text'>" + survey.questions[ index ][ 0 ] + "</div><div class='answer-text'>" + cans + "</div><div class='answer-edit'><a href='#' class='ms-edit-button' data-qnums='1' data-edit='" + parseInt( index + 1 ) + "' data-max='" + survey.qo.length + "'>" + survey.languages.confirm_edit + "</a></div></div>";
			});
			qtexts += '<a href="#" onclick="return false;" data-qnums="1" data-max="' + parseInt( survey.qo.length + 1 ) + '" class="confirm-ms-form button button-secondary button-default">' + survey.languages.confirm_button + '</a>';
			// set progress bar to 100% in case it's enabled
			if ( survey_options[ 20 ] == "1" && survey.display != "all" && survey.display != "" ) {
				modal_survey.find( ".survey-progress-bar .progress" ).css( "width", "100%" );
				modal_survey.find( ".survey-progress-bar .progress_counter" ).html( "100%" );
			}
			modal_survey.find( ".confirm-ms-form-container" ).html( qtexts );
		}

		jQuery( "body" ).on( "click", "#survey-" + survey.survey_id + "-" + unique_key + " .survey_answers", function( e ) {
			e.preventDefault();
			if ( survey_options[ 152 ] != 1 && survey.display == "1" && modal_survey.find( ".section" + ( played_section ) + " .survey_open_answers" ).length < 1 && parseInt( survey_qoptions[ played_section - 1 ][ 0 ] ) < 2 && parseInt( survey_qoptions[ played_section - 1 ][ 1 ] ) < 2 ) {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .ms-next-question" ).trigger( "click" );
			}
		});		
		
		jQuery( "body" ).on( "click", ".ms-edit-button", function( e ) {
			e.preventDefault();
				editmode = "true";
				modal_survey.find( ".modal-survey-confirmation-page" ).css( "display", "none" );
				played_section = jQuery( this ).attr( "data-edit" );
				divide_questions( jQuery( this ).attr( "data-qnums" ), jQuery( this ).attr( "data-max" ) );
		});		
		
		jQuery( "body" ).on( "click", "#survey-" + survey.survey_id + "-" + unique_key + " .confirm-ms-form", function( e ) {
			e.preventDefault();
			survey.confirmed = "true";
			modal_survey.find( ".modal-survey-confirmation-page" ).remove();
			if ( ( ( survey_options[ 125 ] == 1 && survey.form != 'false' ) || survey.form == 'true' ) && ( survey_options[ 126 ] == 1 || survey_options[ 127 ] == 1 ) ) {
				divide_questions( jQuery( this ).attr( "data-qnums" ), jQuery( this ).attr( "data-max" ) );
			}
			else {
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .send-participant-form" ).trigger( "click" );
			}
		})

		jQuery( "body" ).on( "click", ".ms-next-back", function( e ) {
			if ( survey_options[ 154 ] == 1 ) {
				played_section--;
				if ( played_section < 2 ) {
					modal_survey.find( ".ms-next-back" ).css( "visibility", "hidden" );
				}
				divide_questions( jQuery( this ).attr( "data-qnums" ), jQuery( this ).attr( "data-max" ) );
			}
		});
		
		jQuery( "body" ).on( "click", ".ms-next-question", function( e ) {
			e.preventDefault();
			if ( editmode == "true" ) {
				editmode = "false";
				played_section = parseInt( jQuery( this ).attr( "data-max" ) - 1 );
			}
			var bulkanswers = {}, ba = [], bulkopen = {}, bo = [], count_answers_num = 0, start_q = 0, end_q, min_answers = 0, checkquestions = [], missing = "false";
			if ( jQuery( this ).attr( "data-qnums" ) == undefined ) {
				return;
			}
			var thiselem = jQuery( ".send-participant-form-container .button" );
			if ( jQuery( this ).attr( "data-qnums" ).indexOf( "," ) >= 0 ) {
				var qnums_exp = jQuery( this ).attr( "data-qnums" ).split( "," );
				jQuery.each( qnums_exp, function( index, value ) {
					if ( played_section > index ) {
						start_q += parseInt( qnums_exp[ index - 1 ] );
					}
				});
				if ( parseInt( played_section - 1 ) == 0 ) {
					start_q = 0;
				}
				end_q = parseInt( start_q ) + parseInt( qnums_exp[ played_section - 1 ] );
			}
			else {
				start_q = parseInt( played_section - 1 ) * parseInt( jQuery( this ).attr( "data-qnums" ) );
				end_q = start_q + parseInt( jQuery( this ).attr( "data-qnums" ) );
			}
			for ( i = start_q; i < end_q; ++i ) {
				ba = [];bo = [];
				modal_survey.find( ".survey_answers" ).each( function( index ) {
					if ( ( jQuery( this ).hasClass( survey_options[ 134 ] + "selected" ) >= 1 || jQuery( this ).hasClass( "selected" ) ) && ( jQuery( this ).attr( "qid" ) == i ) && ( ! jQuery( this ).hasClass( "ms_rating_question" ) ) ) {
						ba.push( jQuery( this ).attr( "id" ).replace( "survey_answer", "" ) );
						if ( jQuery( this ).hasClass( "survey_open_answers" ) ) {
							bo.push( jQuery( this ).find( ".open_text_answer" ).val() );
						}
						else {
							bo.push( "" );
						}
					}
				})
				if ( ba.length > 0 ) {
					bulkanswers[ i + 1 ] = ba;
					bulkopen[ i + 1 ] = bo;
					count_answers_num += modal_survey.find( ".survey_answers." + survey_options[ 134 ] + "selected[qid=" + i + "]" ).length;
				}
				if ( survey_qoptions[ i ][ 1 ] == "" || survey_qoptions[ i ][ 1 ] == 0 ) {
					min_answers++; 
				}
				else {
					min_answers += parseInt( survey_qoptions[ i ][ 1 ] );
				}
			}
			modal_survey.find( ".each-question" ).each( function( index ) {
				if ( jQuery( this ).css( "display" ) == "block" ) {
					jQuery( this ).find( ".survey_answers" ).each( function( index2 ) {
						if ( jQuery( this ).attr( "class" ).indexOf( "selected" ) >= 0 ) {
							checkquestions[ index + 1 ] = 'true';
						}
					})
				}
			} )
			modal_survey.find( ".each-question" ).each( function( index ) {
				if ( jQuery( this ).css( "display" ) == "block" && checkquestions[ index + 1 ] == undefined ) {
					missing = "true"; 
					jQuery( "html, body" ).animate({
						scrollTop: modal_survey.find( ".sq" + index ).offset().top - ( ( jQuery( window ).height() / 2 ) - ( modal_survey.find( ".sq" + index ).height() / 2 ) )
					}, 500 );
					return;					
				}
			})
		
			if ( count_answers_num < min_answers ) {
				var bulkselanswers = [], missinganswer;
				modal_survey.find( ".survey_element." + survey_options[ 134 ] + "selected" ).each( function( index ) {
					bulkselanswers[ jQuery( this ).attr( "qid" ) ] = 1;
				});
				for (i = start_q; i < end_q; i++) {
					if ( bulkselanswers[ i ] == undefined ) {
						missinganswer = modal_survey.find( " .sq" + i );
						break;
					}
					else {
						if ( survey_qoptions[ i ][ 1 ] > modal_survey.find( ".survey_answers." + survey_options[ 134 ] + "selected[qid=" + i + "]" ).length ) {
							missinganswer = modal_survey.find( " .sq" + i );
							break;
						}
					}
				}
				jQuery( "html, body" ).animate({
					scrollTop: missinganswer.offset().top - ( ( jQuery( window ).height() / 2 ) - ( missinganswer.height() / 2 ) )
				}, 500 );
				return false;
			}
			else {
				if ( missing == "false" ) {
					played_section++;
					divide_questions( jQuery( this ).attr( "data-qnums" ), jQuery( this ).attr( "data-max" ) );
					if ( survey.scrollto == "true" ) {
						jQuery( "html, body" ).animate({
							scrollTop: modal_survey.offset().top - 100
						}, 500 );
					}
				}
			}
		});
		
		function send_bulk_answers() {
			var bulkanswers = {}, ba = [], bulkopen = {}, bo = [], count_answers_num = 0, min_answers = 0, missing = "false", checkquestions = [];
			var thiselem = jQuery( ".send-participant-form-container .button" );
			for ( i = 0; i < survey.questions.length; ++i ) {
				ba = [];bo = [];
				modal_survey.find( ".survey_answers" ).each( function( index ) {
					if ( ( jQuery( this ).hasClass( survey_options[ 134 ] + "selected" ) >= 1 || jQuery( this ).hasClass( "selected" ) ) && ( jQuery( this ).attr( "qid" ) == i ) && ( ! jQuery( this ).hasClass( "ms_rating_question" ) ) ) {
						ba.push( jQuery( this ).attr( "id" ).replace( "survey_answer", "" ) );
						if ( jQuery( this ).hasClass( "survey_open_answers" ) ) {
							bo.push( jQuery( this ).find( ".open_text_answer" ).val() );
						}
						else {
							bo.push( "" );
						}
					}
				})
				if ( ba.length > 0 ) {
					bulkanswers[ i + 1 ] = ba;
					bulkopen[ i + 1 ] = bo;
					count_answers_num += modal_survey.find( ".survey_answers." + survey_options[ 134 ] + "selected[qid=" + i + "]" ).length;
				}
				if ( survey_qoptions[ i ][ 1 ] == "" && survey_qoptions[ i ][ 1 ] == 0 ) {
					min_answers++; 
				}
				else {
					min_answers += parseInt( survey_qoptions[ i ][ 1 ] );
				}
				if ( survey_qoptions[ i ][ 3 ] == 1 ) {
					thiselem.parent().append( '<div id="survey_preloader"><img width="20" src="' + survey.plugin_url + '/templates/assets/img/' + survey_options[ 133 ] + '.gif"></div>' );
				}
				else {
					thiselem.append( '<div id="survey_preloader"><img width="20" src="' + survey.plugin_url + '/templates/assets/img/' + survey_options[ 133 ] + '.gif"></div>' );
				}
				var thissurvey = [], qa = {}, thisscore = 0, thiscorrect = 0;
				var regExp = /\[(.*?)\]/;
				var matches = regExp.exec( survey.questions[ i ][ 0 ] );
				if ( matches != null ) {
					if ( surveycatscore[ matches[ 1 ] ] == undefined ) {
						surveycatscore[ matches[ 1 ] ] = 0;
					}
				}
				jQuery.map( survey.questions[ i ], function( val, i ) {
					if ( i > 0 ) {
						var matches2 = regExp.exec( val );
						if ( matches2 != null ) {
							if ( surveycatscore[ matches2[ 1 ] ] == undefined && ! jQuery.isNumeric( matches2[ 1 ] ) ) {
								surveycatscore[ matches2[ 1 ] ] = 0;
							}
						}
					}
				})
				jQuery.map( ba, function( val, f ) {
					if ( parseInt( survey_aoptions[ parseInt( i + 1 ) + "_" + val ][ 4 ] ) >= 0 ) {
						thisscore = parseInt( survey_aoptions[ parseInt( i + 1 ) + "_" + val ][ 4 ] );
					}
					else {
						thisscore = 0;
					}
					if ( parseInt( survey_aoptions[ parseInt( i + 1 ) + "_" + val ][ 5 ] ) >= 0 ) {
						thiscorrect = parseInt( survey_aoptions[ parseInt( i + 1 ) + "_" + val ][ 5 ] );
					}
					else {
						thiscorrect = 0;
					}
					if ( matches != null ) {
						if ( matches[ 1 ] != undefined ) {
							surveycatscore[ matches[ 1 ] ] = parseFloat( surveycatscore[ matches[ 1 ] ] ) + thisscore;
						}
					}
					var matches3 = regExp.exec( survey.questions[ i ][ parseInt( val ) ] );
					if ( matches3 != null ) {
							if ( matches3 != null ) {
								if ( matches3[ 1 ] != undefined && ! jQuery.isNumeric( matches3[ 1 ] ) ) {
									surveycatscore[ matches3[ 1 ] ] = parseFloat( surveycatscore[ matches3[ 1 ] ] ) + thisscore;
								}
							}
					}
					surveyscore = parseFloat( surveyscore ) + thisscore;
					surveycorrect = parseInt( surveycorrect ) + thiscorrect;
					question_score[ parseFloat( i + 1 ) ] = thisscore;
					question_correct[ parseInt( i + 1 ) ] = thiscorrect;
					question_choice[ parseInt( i + 1 ) ] = ba;
				});
				if ( survey.preview == undefined ) {
					survey.preview = "false";
				}
			}
			qa[ 'sid' ] = survey.survey_id;
			qa[ 'bulkans' ] = bulkanswers;
			qa[ 'auto_id' ] = survey.auto_id;
			qa[ 'postid' ] = survey.postid;
			qa[ 'bulkopen' ] = bulkopen;
			thissurvey.push( qa );
			rmdni = true;
			
			modal_survey.find( ".each-question" ).each( function( index ) {
				if ( jQuery( this ).css( "display" ) == "block" ) {
					jQuery( this ).find( ".survey_answers" ).each( function( index2 ) {
						if ( jQuery( this ).attr( "class" ).indexOf( "selected" ) >= 0 ) {
							checkquestions[ index + 1 ] = 'true';
						}
					})
				}
			} )
			modal_survey.find( ".each-question" ).each( function( index ) {
				if ( jQuery( this ).css( "display" ) == "block" && checkquestions[ index + 1 ] == undefined ) {
					missing = "true";
					jQuery( "html, body" ).animate({
						scrollTop: modal_survey.find( ".sq" + index ).offset().top - ( ( jQuery( window ).height() / 2 ) - ( modal_survey.find( ".sq" + index ).height() / 2 ) )
					}, 500 );
					return;					
				}
			})
			
			if ( missing == "false" ) {
				if ( count_answers_num < min_answers ) {
					var bulkselanswers = [], missinganswer;
					modal_survey.find( ".survey_element." + survey_options[ 134 ] + "selected" ).each( function( index ) {
						bulkselanswers[ jQuery( this ).attr( "qid" ) ] = 1;
					});
					for (i = 0; i < survey.questions.length; i++) {
						if ( bulkselanswers[ i ] == undefined ) {
							missinganswer = modal_survey.find( " .sq" + i );
							break;
						}
						else {
							if ( survey_qoptions[ i ][ 1 ] > modal_survey.find( ".survey_answers." + survey_options[ 134 ] + "selected[qid=" + i + "]" ).length ) {
								missinganswer = modal_survey.find( " .sq" + i );
								break;
							}
						}
					}
					jQuery( "html, body" ).animate({
						scrollTop: missinganswer.offset().top - ( ( jQuery( window ).height() / 2 ) - ( missinganswer.height() / 2 ) )
					}, 500 );
					return false;
				}
				else {
					var data = {
						action: 'ajax_survey_answer',
						sspcmd: 'bulksave',
						endcontent: true,
						options: JSON.stringify( thissurvey ),
						preview: survey.preview
						};
						jQuery.post( survey.admin_url, data, function( response ) {
							if ( response.toLowerCase().indexOf( "success" ) >= 0 ) {	
								jQuery( "#survey_preloader" ).remove();
								return true;
							}
							rmdni = false;
							return true;
						}).fail(function() {
								jQuery( "#survey_preloader" ).remove();						
								rmdni = false;
								sanswers = [];
								return false;
						  })
				}
				if ( count_answers_num >= survey.questions.length ) {
					return true;
				}
				else {
					return false;
				}
			}
		}
		
		/*		Email Validation Function		*/
		function isValidEmailAddress( emailAddress ) {
			var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
			return pattern.test( emailAddress );
		};
		
		function detectmob() {
		var Uagent = navigator.userAgent||navigator.vendor||window.opera;
			return(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(Uagent)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(Uagent.substr(0,4))); 
		}
	
		function close_survey() {
				if ( survey_options[ 158 ] == "fade" ) {
					modal_survey.fadeOut( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
						modal_survey.addClass( "hide-important" );
					} );
				}
				else {
					if (survey_options[0]=="bottom") {
						modal_survey.animate({ bottom: "-" + parseInt( modal_survey.height() + 100 ) + "px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() { 
							modal_survey.css( "bottom", "-" + parseInt( modal_survey.height() + 100 ) + "px" );
							modal_survey.css( "display", "table" );
						})
					}
					if ( survey_options[ 0 ] == "center" ) {
						if ( survey.align == "left" ) {
							modal_survey.animate({ left: "-5000px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
								modal_survey.css( "bottom", "-" + parseInt( modal_survey.height() + 100 ) + "px" );
								modal_survey.css( "display", "table" );
							})
						}
						else {
							modal_survey.animate({ left: "-5000px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
								modal_survey.css( "bottom", "-" + parseInt( modal_survey.height() + 100 ) + "px" );
								modal_survey.css( "display", "table" );
							})
						}
					}
					if ( survey_options[ 0 ] == "top" ) {
						modal_survey.animate({ top: "-" + parseInt( modal_survey.height() + 100 ) + "px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
							modal_survey.css( "bottom", "-" + parseInt( modal_survey.height() + 100 ) + "px" );
							modal_survey.css( "display", "table" );
						})
					}
				}
				if ( jQuery( "#bglock" ).length > 0 && jQuery( "#bglock" ).css( "display", "block" ) && parseInt( survey_options[ 13 ] ) == 1) {
					jQuery( "#bglock" ).fadeOut( 1000, function() { 
						jQuery( "#bglock" ).remove();
					});
				}
				played_question = -1;
		}
		jQuery( "body" ).on( "change", ".mspform_confirmation", function() {
			if ( jQuery( this ).prop( "checked" ) == true ) {
				jQuery( this ).val( "0" );
			}
			else {
				jQuery( this ).val( "1" );
			}
		})
		function display_chart() {
			var chartstyle = [];
			if ( chartstyleparams != undefined && chartstyleparams != "" ) {
				chartstyleparams = chartstyleparams.replace(/[|]/g, "'").split( "," );
			}
			if ( chartstyleparams != "" ) {
				jQuery.map( chartstyleparams, function( val, i ) {
					var thisval = val.split( ":" );
					if ( thisval[ 1 ] == undefined ) {
						chartstyle[ 'style' ] = thisval[ 0 ];
					}
					else {
						chartstyle[ thisval[ 0 ] ] = thisval[ 1 ].replace(/[;]/g, ",").replace(/[.]/g, ":");
					}
				} )
			}
			if ( chartstyle[ 'style' ] != undefined ) {
				charttype = chartstyle[ 'style' ];
				delete chartstyle[ 'style' ];
			}
			if ( ( charttype != undefined ) && ( played_question == endstep ) ) {
				jQuery.map( question_name, function( val, i ) {
				var exist = 0;
					if ( chartmode == "displayischart" ) {
						chartelems[ 'answer' ] = val;
						chartelems[ 'count' ] = question_score[ i + 1 ];
					}
					if ( chartmode == "displayirchart" ) {
						chartelems[ 'answer' ] = val;
						chartelems[ 'count' ] = question_choice[ i + 1 ][ 0 ];					
					}
					if ( chartmode == "displayicchart" ) {
						chartelems[ 'answer' ] = val;
						chartelems[ 'count' ] = question_correct[ i + 1 ];					
					}
					if ( chartelems[ 'count' ] == undefined ) {
						chartelems[ 'count' ] = 0;
					}
					if ( val != undefined ) {
						if ( jQuery.isEmptyObject( chartparams ) ) {
									chartparams.push( {"answer": val, "count": chartelems[ 'count' ]} );							
						}
						else {
							jQuery.map( chartparams, function( cp, k ) {
								if ( cp[ 'answer' ] == chartelems[ 'answer' ] ) {
									chartparams[ k ][ 'count' ] = chartparams[ k ][ 'count' ] + chartelems[ 'count' ];
									exist = 1;
								}
							})
							if ( exist == 0 ) {
								chartparams.push( {"answer": val, "count": chartelems[ 'count' ]} );
							}
						}
					}
				})
				if ( charttype != undefined ) {
					chartstyle[ "style" ] = charttype;
					chartstyle[ "max" ] = 0;
					jQuery( "#survey-results-" + random ).pmsresults({ "style": chartstyle, "datas": [ chartparams ] });
				}
			}
		}

		jQuery( "body" ).on( "click", ".backbutton", function(e) {
		if ( rmdni == false && survey.last_answer ) {
			abortTimer();
			rmdni = true;
			var data = {
					action: 'ajax_survey_back',
					qid: ( survey.last_question + 1 ),
					sid: survey.survey_id,
					la: survey.last_answer,
					lo: survey.last_open,
					preview: survey.preview
				};
				var back_aopts = survey_aoptions[ parseInt( survey.last_question + 1 ) + "_" + survey.last_answer ];
				surveyscore = parseInt( surveyscore ) - parseInt( back_aopts[ 4 ] );
				if ( back_aopts[ 5 ] == 1 ) {
					surveycorrect = parseInt( surveycorrect ) - 1;
				}
				question_score[ parseInt( survey.last_question + 1 ) ] = 0;
				question_correct[ parseInt( survey.last_question + 1 ) ] = 0;
				question_choice[ parseInt( survey.last_question + 1 ) ] = '';
				var regExp = /\[(.*?)\]/;
				var matches = regExp.exec( survey.questions[ survey.last_question + 1 ][ parseInt( survey.last_answer ) ] );
				if ( matches != null ) {
					if ( surveycatscore[ matches[ 1 ] ] != undefined && matches[ 1 ] != "-" ) {
						surveycatscore[ matches[ 1 ] ] = surveycatscore[ matches[ 1 ] ] - parseInt( back_aopts[ 4 ] );
					}
				}
				var bbutton = jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .backbutton>img" ).attr( "src" );
				jQuery( "#survey-" + survey.survey_id + "-" + unique_key + " .backbutton>img" ).attr( "src", survey.plugin_url + '/templates/assets/img/' + survey_options[ 133 ] + ".gif" );
				jQuery.post( survey.admin_url, data, function( response ) {
					if ( response.toLowerCase().indexOf( "success" ) >= 0 ) {
						modal_survey.find( ".backbutton" ).attr( "src", bbutton );
						played_question = survey.last_question;
						continue_survey();
						rmdni = false;
					}
					rmdni = false;
				}).fail(function() {
						modal_survey.find( ".backbutton" ).attr( "src", bbutton );
						rmdni = false;
				})
			}
			survey.last_answer = "";
		});
		
		jQuery( "body" ).on( "click", "#close_survey, #bglock", function(e) {
			e.preventDefault();
			if ( parseInt( survey_options[ 14 ] ) == 1 ) {
				if ( jQuery( "#bglock" ).length > 0 && ( jQuery( "#bglock" ).css( "display" ) == "block" ) && ( parseInt( survey_options[ 13 ] ) ) == 1 ) {
					jQuery( "#bglock" ).fadeOut( 1000, function() {
						jQuery( "#bglock" ).remove();
					});
				}
				if ( survey_options[ 158 ] == "fade" ) {
					modal_survey.fadeOut( parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() {
						modal_survey.addClass( "hide-important" );
					} )
				}
				else {
					if ( survey_options[ 0 ] == "bottom" ) {
						modal_survey.animate({ bottom: "-" + parseInt( modal_survey.height() + 100 ) + "px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() { modal_survey.css( "z-index", "-1" ); } )
					}
					if ( survey_options[ 0 ] == "center" ) {
						if ( survey.align == "left" ) {
							modal_survey.animate({ left: "-5000px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() { modal_survey.css( "z-index", "-1" ); } );
						}
						else {
							modal_survey.animate({ right: "-5000px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() { modal_survey.css( "z-index", "-1" ); } );
						}
					}
					if ( survey_options[ 0 ] == "top" ) {
						modal_survey.animate({ top: "-" + parseInt( modal_survey.height() + 100 ) + "px" }, parseInt( survey_options[ 11 ] ), survey_options[ 1 ], function() { modal_survey.css( "z-index", "-1" ); } )
					}
				}
				played_question = -1;
			}
		});	
	}
});
$.fn[ pluginName ] = function ( options ) {
		var args = arguments;
			if ( typeof options == undefined ) {
				var options = {};
			}
			if ( options === undefined || typeof options === 'object' ) {
				return this.each( function () {
					if ( ! $.data( this, 'plugin_' + pluginName ) ) {
						options.selector = this;					
						$.data( this, 'plugin_' + pluginName, new Plugin( this, options ) );
					}
				});
			} else if ( typeof options === 'string' && options[ 0 ] !== '_' && options !== 'init' ) {
				var returns;
				this.each( function () {
					var instance = $.data( this, 'plugin_' + pluginName );
					if ( instance instanceof Plugin && typeof instance[ options ] === 'function' ) {
						options.selector = this;					
						returns = instance[ options ].apply( instance, Array.prototype.slice.call( args, 1 ) );
					}
					if ( options === 'destroy' ) {
					  $.data( this, 'plugin_' + pluginName, null );
					}
				});
				return returns !== undefined ? returns : this;
			}
};
})( jQuery, window, document );