<?php
/**
 * Class CTF_Parse
 *
 *
 * @since 2.0
 */
namespace TwitterFeed;

use TwitterFeed\CtfFeed;
use TwitterFeed\V2\CtfOauthConnect;
use TwitterFeed\CTF_GDPR_Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class CTF_Parse{


    /**
     * Get Tweet ID
     *
     * @since 2.0
     */
    public static function get_tweet_id( $data ) {
        return $data['id_str'];
    }

    public static function get_post_id( $data ) {
	    return $data['id_str'];
    }

    /**
     * Get Tweet Author User name
     *
     * @since 2.0
     */
	public static function get_user_name( $data ) {
		if ( ! empty( $data['screen_name'] ) ) {
			return $data['screen_name'];
		}
		if ( ! empty( $data['user']['screen_name'] ) ) {
			return $data['user']['screen_name'];
		}
		return '';
	}

    /**
	 * Get Tweet Author Name
	 *
	 * @since 2.0
	 */
	public static function get_author_name( $data ) {
        return strtolower( $data['user']['name'] );
    }

	/**
	 * Get Tweet Author Name
	 *
	 * @since 2.0
	 */
	public static function get_display_author_name( $data ) {
		return $data['user']['name'];
	}

    /**
	 * Get Tweet Author Screen Name
	 *
	 * @since 2.0
	 */
    public static function get_author_screen_name( $data ) {
		if ( ! empty( $data['user'] ) ) {
			if ( is_array( $data['user'] ) ) {
				return strtolower( $data['user']['screen_name'] );
			} else {
				return strtolower( $data['user'] );
			}
		}
	    if ( ! empty( $data['screen_name'] ) ) {
		    return strtolower( $data['screen_name'] );
	    }
		return '';
    }

    public static function get_quoted_name( $data ) {
        return $data['user']['name'];
    }

    public static function get_quoted_screen_name( $data ) {
        return $data['user']['screen_name'];
    }

    /**
	 * Get Tweet Verified
	 *
	 * @since 2.0
	*/
    public static function get_quoted_verified( $data ) {
        return $data['user']['verified'];
    }


    /**
	 * Get Tweet Post
	 *
	 * @since 2.0
	*/
    public static function get_post($tweet_set) {
        if ( isset( $tweet_set['retweeted_status'] ) ) {
            return $tweet_set['retweeted_status'];
        } else {
            return $tweet_set;
        }
    }

    /**
     * Get Tweet Avatar URL
     *
     * @since 2.0
    */
    public static function get_avatar_url( $post, $feed_options ) {
        if ( CTF_GDPR_Integrations::doing_gdpr( $feed_options ) ) {
            return trailingslashit( CTF_PLUGIN_URL ) . 'img/placeholder.png';
        }

        return self::get_avatar( $post );
    }

    /**
     * Get Tweet Avatar
     *
     * @since 2.0
    */
    public static function get_avatar( $data ) {
        if ( isset( $data['retweeted_status'] ) ) {
            return $data['retweeted_status']['user']['profile_image_url_https'];
        } elseif ( isset( $data['user'] ) ) {
            return $data['user']['profile_image_url_https'];
        } elseif ( isset( $data['profile_image_url_https'] ) ) {
            return $data['profile_image_url_https'];
        }
        return '';
    }

    /**
     * Get Tweet UTS Offset
     *
     * @since 2.0
    */
    public static function get_utc_offset ( $data ) {
		if ( empty( $data['user']['utc_offset'] ) ) {
			return 0;
		}
        return $data['user']['utc_offset'];
    }

    /**
     * Get Tweet Original TimeStamp
     *
     * @since 2.0
    */
    public static function get_original_timestamp( $data ) {
        return $data['created_at'];
    }

    /**
     * Get Tweet Author Verified
     *
     * @since 2.0
    */
    public static function get_verified ( $data ) {
        return $data['user']['verified'];
    }


    /**
     * Get Generic Header Text
     *
     * @since 2.0
    */
    public static function get_generic_header_text( $data ) {
        if ( $data['type'] === 'search' || $data['type'] === 'hashtag' ) {
            $using_custom = $data['headertext'] != '';
            $raw_header_text = $using_custom ? $data['headertext'] : $data['feed_term'];

            //List multiple terms
            $hashtags = explode(" OR ", $data['feed_term'] ?? '');
            if ( ! $using_custom ) {
                $default_header_text = '';
                $h_index = 0;
                foreach ( $hashtags as $hashtag ) {
                    if( $h_index > 0 ) $default_header_text .= ', ';
                    $default_header_text .= $hashtag;
                    $h_index++;
                }
            } else {
                $default_header_text = $data['headertext'];
            }

            $default_header_text = str_replace( ' -filter:retweets', '', $default_header_text );


            return $default_header_text;

        } else {
            $default_header_text = 'Twitter';
            // $url_part = $data['screenname']; //Need to get screenname here
            return $default_header_text;
        }

        //Header for combined feed types
        if ( ! empty( $data['feed_types_and_terms'] ) ) {
            if ( $data['headertext'] != '' ) {
                $default_header_text = $data['headertext'];

                if ( $data['feed_types_and_terms'][0][0] === 'search' || $data['feed_types_and_terms'][0][0] === 'hashtag' ) {
                    $raw_header_text = $data['feed_types_and_terms'][0][1];
                }

                return $default_header_text;

            } else {
                $default_header_text = '';
                $i_term = 0;
                foreach ( $data['feed_types_and_terms'] as $feed_set ) {
                    if ( $feed_set[0] == 'lists' ) {
                        $default_header_text .= '';
                    } else {
                        if ( $i_term > 0 ) {
                            $default_header_text .= ', ';
                        }
                        if ( $feed_set[0] == 'usertimeline' ) {
                            $default_header_text .= '@';
                        }
                        $default_header_text .= $feed_set[1];
                    }
                    $i_term++;
                }
            }

            if ( empty( $default_header_text ) ) {
                return $default_header_text = 'Twitter';
            }

        }
    }


    /**
     * Get Generic Header URL
     *
     * @since 2.0
    */
    public static function get_generic_header_url ( $data ) {
        $hashtags = isset($data['feed_term']) ? explode(" OR ", $data['feed_term']) : '';
        if ( $data['type'] === 'search' || $data['type'] === 'hashtag' ) {
            if ( $data['type'] === 'hashtag' ) {
                $url_part = 'hashtag/' . str_replace("#", "", $hashtags[0]);
            } else {
                $url_part = 'search?q=' . rawurlencode( str_replace( array( ', ', "'" ), array( ' OR ', '"' ), $data['feed_term'] ) );
            }

            return $url_part;
        }

        if ( ! empty( $data['feed_types_and_terms'] ) ) {
            if ( $data['feed_types_and_terms'][0][0] === 'search' || $data['feed_types_and_terms'][0][0] === 'hashtag' ) {
                $raw_header_text = $data['feed_types_and_terms'][0][1];
                //List multiple terms
                $hashtags = explode( " OR ", $data['feed_types_and_terms'][0][1] );

                if ( $data['feed_types_and_terms'][0][0] === 'hashtag' ) {
                    $url_part = 'hashtag/' . str_replace( "#", "", $hashtags[0] );

                    return $url_part;
                } else {
                    $url_part = 'search?q=' . rawurlencode( str_replace( array( ', ', "'" ), array(
                            ' OR ',
                            '"'
                        ), $data['feed_types_and_terms'][0][1] ) );

                    return $url_part;
                }
            }
        }

    }


    /**
     * Get Header Text
     *
     * @since 2.0
    */
    public static function get_header_text( $header_info, $feed_options ) {
        if ( empty( $header_info ) || ! is_array( $header_info ) ) {
            return '';
        }

        if ( $feed_options['headertext'] !== '' ) {
            $header_text = $feed_options['headertext'];
            return $header_text;
        } else {
            $header_text = $header_info['name'];
            return $header_text;
        }
    }


    /**
     * Get Header Description
     *
     * @since 2.0
    */
    public static function get_header_description( $data ) {
        return $data['description'];
    }



    /**
     * Get User Header JSON info
     *
     * @since 2.0
    */
    public static function get_user_header_json( $data, $post_info ) {
	    $type = ! empty( $data['type'] ) ? $data['type'] : 'usertimeline';

	    $types_and_terms = isset($data['feed_types_and_terms']) ? $data['feed_types_and_terms'] : [];

	    $timelines_included = array();
	    foreach ( $types_and_terms as $type_and_term ) {
		    if ( $type_and_term[0] === 'usertimeline' ) {
			    $timelines_included[] = str_replace( '@', '', strtolower( $type_and_term[1] ) );
		    }
	    }

	    if ( $type === 'usertimeline' ) {
		    if ( ! empty( $post_info[0]['user'] ) ) {
				return $post_info[0]['user'];
		    }

	    }

		return array();
    }


    /**
     * Get User Header Avatar
     *
     * @since 2.0
    */
    public static function get_header_avatar( $data, $feed_options = array() ) {
        $settings = ctf_get_database_settings();
        if ( CTF_GDPR_Integrations::doing_gdpr( $settings ) ) {
            $avatar = trailingslashit( CTF_PLUGIN_URL ) . 'img/placeholder.png';
        } else {
            $avatar = $data['profile_image_url_https'];
        }

        return $avatar;
    }

    public static function get_quoted_tc( $data ) {

        $quoted = false;

        // check for quoted
        if ( isset( $data['quoted_status'] ) ) {
            $quoted = $data['quoted_status'];
            return $quoted;
        } else {
            unset( $quoted );
        }

    }

    public static function get_quoted_media( $data, $num_media ) {
        //Quoted Tweets Media
        $quoted_media = [];

        if ( isset( $data['extended_entities']['media'] ) ) {

            $num_media = count( $data['extended_entities']['media'] );
            for( $ii = 0; $ii < $num_media; $ii++ ) {
                if ( $data['extended_entities']['media'][$ii]['type'] == 'video' || $data['extended_entities']['media'][$ii]['type'] == 'animated_gif' ) {
                    $quoted_media[$ii]['url'] = $data['extended_entities']['media'][$ii]['video_info']['variants'][$ii]['url'];
                } else {
                    $quoted_media[$ii]['url'] = $data['extended_entities']['media'][$ii]['media_url_https'];
                }
                $quoted_media[$ii]['type'] = $data['extended_entities']['media'][$ii]['type'];
                if ( $quoted_media[$ii]['type'] == 'video' ) {
                    $quoted_media[$ii]['video_atts'] = 'controls';
                } elseif ( $quoted_media[$ii]['type'] == 'animated_gif' ) {
                    $quoted_media[$ii]['video_atts'] = 'controls loop autoplay muted';
                }
                $quoted_media[$ii]['poster'] = $data['extended_entities']['media'][$ii]['media_url_https'];
            }

        } elseif ( isset( $data['entities']['media'] ) ) {

            $num_media = count( $data['entities']['media'] );
            for( $ii = 0; $ii < $num_media; $ii++ ) {
                if ( $data['entities']['media'][$ii]['type'] == 'video' || $data['entities']['media'][$ii]['type'] == 'animated_gif' ) {
                    $quoted_media[$ii]['url'] = $data['entities']['media'][$ii]['video_info']['variants'][$ii]['url'];
                } else {
                    $quoted_media[$ii]['url'] = $data['entities']['media'][$ii]['media_url_https'];
                }
                $quoted_media[$ii]['type'] = $data['entities']['media'][$ii]['type'];
                if ( $quoted_media[$ii]['type'] == 'video' ) {
                    $quoted_media[$ii]['video_atts'] = 'controls';
                } elseif ( $quoted_media[$ii]['type'] == 'animated_gif' ) {
                    $quoted_media[$ii]['video_atts'] = 'controls loop autoplay muted';
                }
                $quoted_media[$ii]['poster'] = $data['entities']['media'][$ii]['media_url_https'];
            }

        }

        return $quoted_media;
    }

    /**
     * Get Feed Classes
     *
     * @since 2.0
    */
    public static function get_feed_classes( $feed_options, $check_for_duplicates, $feed_id = false) {
        if( ctf_doing_customizer( $feed_options ) ){
            return ' :class="$parent.getFeedClasses()" ';
        }else{
            // Every value below reaches the container's class attribute. A shortcode may
            // set them directly (legacy-shortcode installs skip the attribute allowlist), and
            // a bare double quote breaks out of the attribute, so each is reduced to the
            // identifier shape it is documented to have (SMASH-1796).
            $allowed_types = array( 'usertimeline', 'hometimeline', 'hashtag', 'search', 'mentionstimeline', 'lists', 'multiple' );
            $type          = CTF_Parse::get_feed_type( $feed_options );
            $type          = in_array( $type, $allowed_types, true ) ? $type : 'usertimeline';

            // The "CSS class" field legitimately holds several space-separated classes, so
            // sanitize per token and keep the separators rather than collapsing them.
            $ctf_sanitize_classes = static function ( $value ) {
                $tokens = preg_split( '/\s+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
                return implode( ' ', array_filter( array_map( 'sanitize_html_class', (array) $tokens ), 'strlen' ) );
            };

            $ctf_feed_classes = 'ctf ctf-type-' . $type;
            $ctf_feed_classes .= \ctf_should_rebrand_to_x() ? ' ctf-rebranded' : '';
            $ctf_feed_classes .= ($feed_id !== false ) ?  ' ctf-feed-' . absint( $feed_id ) : '';
            $ctf_feed_classes .= ' ' . $ctf_sanitize_classes( $feed_options['class'] ) . ' ctf-styles';
            $ctf_feed_classes .= ($feed_options['layout']) ?  ' ctf-' . sanitize_html_class( $feed_options['layout'] ): '';
            $ctf_feed_classes .= ( isset( $feed_options['tweetpoststyle'] ) ) ?  ' ctf-' . sanitize_html_class( $feed_options['tweetpoststyle'] ) . '-style' : '';
            if ( ! empty( $feed_options['height'] ) ) $ctf_feed_classes .= ' ctf-fixed-height';
            $ctf_feed_classes .= $feed_options['width_mobile_no_fixed'] ? ' ctf-width-resp' : '';
            if ( $check_for_duplicates ) { $ctf_feed_classes .= ' ctf-no-duplicates'; }
            if( isset($feed_options['colorpalette']) && $feed_options['colorpalette'] !== 'inherit' && $feed_id !== false ){
                $palette       = sanitize_html_class( $feed_options['colorpalette'] );
                $feed_id_class = $palette === 'custom' ? ('_' . absint( $feed_id )) : '';
                $ctf_feed_classes .= ' ctf_palette_' . $palette . $feed_id_class;
            }
            $ctf_feed_classes = apply_filters( 'ctf_feed_classes', $ctf_feed_classes );
            return 'class=" ' . esc_attr( $ctf_feed_classes ) .'" ';
        }

    }

    public static function get_tweet_count( $data ) {

        if ( isset( $data['statuses'] ) && is_array( $data['statuses'] ) ) {
            $tweet_count = count( $data['statuses'] );
        } elseif ( is_array( $data ) ) {
            $tweet_count = count( $data );
        } else {
            $tweet_count = 0;
        }

        return $tweet_count;
    }
    /**
     * Get Feed Type
     *
     * @since 2.0
    */
    public static function get_feed_type( $feed_options ) {
        $ctf_feed_type = ! empty ( $feed_options['type'] ) ? $feed_options['type'] : 'multiple';
        return $ctf_feed_type;
    }

    public static function get_retweet_count( $data ) {
        if ( isset( $data['retweeted_status']['retweet_count'] ) ) {
            return $data['retweeted_status']['retweet_count'];
        } else {
            return $data['retweet_count'];
        }
    }

    public static function get_favorite_count( $data ) {
        if ( isset( $data['retweeted_status']['favorite_count'] ) ) {
            return $data['retweeted_status']['favorite_count'];
        } else {
            return $data['favorite_count'];
        }
    }

     /**
     * Get Global Twitter Feed CSS
     *
     * @since 2.0
     * @return array
    */
    public static function parse_css_style ( $css_array ) {
        $style = '';
        $color_elements = [
            'color',
            'background',
            'background-color'
        ];

        $size_elements = [
            'border-radius',
            'height',
            'width',
            'font-size',
            'margin',
            'margin-top',
            'margin-bottom',
            'margin-left',
            'margin-right',
            'padding',
            'padding-top',
            'padding-bottom',
            'padding-left',
            'padding-right'
        ];

        $border_elements = [
            'border',
            'border-top',
            'border-bottom',
            'border-left',
            'border-right'
        ];

		// These values are feed settings. The builder only runs them through
		// sanitize_text_field(), and on sites with legacy shortcode support they are
		// Contributor-settable through the shortcode. Escaping cannot make arbitrary
		// CSS safe, so each value is validated against its property's grammar and
		// dropped when it does not match (SMASH-1770, CVE-2026-84909).
		foreach ( $css_array as $element ) {
			$items_css = '';
			if ( isset( $element['properties'] ) ) {
				foreach ( $element['properties'] as $property => $item ) {
					if ( in_array( $property, $color_elements, true ) && ! empty( $item['value'] ) && '#' !== $item['value'] ) {
						$color = self::css_color( $item['value'] );
						if ( false !== $color ) {
							$items_css .= $property . ':' . $color;
							$items_css .= isset( $item['important'] ) ? '!important;' : ';';
						}
					}
					if ( in_array( $property, $size_elements, true ) && ! empty( $item['value'] )
						&& '0' !== $item['value'] && 'inherit' !== $item['value'] ) {
						$number = self::css_number( $item['value'] );
						$unit   = self::css_unit( isset( $item['unit'] ) ? $item['unit'] : 'px' );
						if ( false !== $number && false !== $unit ) {
							$items_css .= $property . ':' . $number . $unit;
							$items_css .= isset( $item['important'] ) ? '!important;' : ';';
						}
					}
					if ( in_array( $property, $border_elements, true ) && ! empty( $item['size'] ) && '0' !== $item['size']
						&& ! empty( $item['color'] ) && '#' !== $item['color'] ) {
						$size        = self::css_number( $item['size'] );
						$color       = self::css_color( $item['color'] );
						$borderstyle = self::css_border_style( isset( $item['style'] ) ? $item['style'] : 'solid' );
						if ( false !== $size && false !== $color && false !== $borderstyle ) {
							$items_css .= $property . ':' . $size . 'px ' . $borderstyle . ' ' . $color;
							$items_css .= isset( $item['important'] ) ? '!important;' : ';';
						}
					}
				}
				// Second layer; the feed id is cast at its source. A brace or comment opener
				// here would end the rule early and turn the rest into new rules.
				$selector = isset( $element['selector'] ) ? (string) $element['selector'] : '';
				if ( '' !== $selector && ! preg_match( '/[{}<>;]|\/\*|\\\\/', $selector ) ) {
					$style .= ! empty( $items_css ) ? $selector . '{' . $items_css . '}' : '';
				}
			}
		}

		return $style;
	}

	/**
	 * Validate a settings value used as a CSS colour. SMASH-1770.
	 *
	 * Accepts hex, rgb()/rgba(), hsl()/hsla() and the named colours; refuses the rest.
	 *
	 * @param string $value Raw setting value.
	 * @return string|false The value if it is a colour, false otherwise.
	 */
	public static function css_color( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value || '#' === $value ) {
			return false;
		}

		if ( preg_match( '/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value ) ) {
			return $value;
		}

		$number     = '[+-]?(?:\d+|\d*\.\d+)';
		$number_pct = $number . '%?';
		$sep        = '\s*[,\s]\s*';
		$alpha      = '(?:[,\/]\s*' . $number_pct . '\s*)?';

		$rgb = '/^rgba?\(\s*' . $number_pct . $sep . $number_pct . $sep . $number_pct . '\s*' . $alpha . '\)$/i';
		$hsl = '/^hsla?\(\s*' . $number . '(?:deg|grad|rad|turn)?' . $sep . $number . '%'
			. $sep . $number . '%\s*' . $alpha . '\)$/i';

		if ( preg_match( $rgb, $value ) || preg_match( $hsl, $value ) ) {
			return $value;
		}

		if ( in_array( strtolower( $value ), self::css_named_colors(), true ) ) {
			return $value;
		}

		return false;
	}

	/**
	 * The CSS named colours and colour keywords.
	 *
	 * Listed in full so a legacy value such as bgcolor="white" is not discarded.
	 *
	 * @return array
	 */
	public static function css_named_colors() {
		static $colors = null;

		if ( null !== $colors ) {
			return $colors;
		}

		$colors = explode(
			' ',
			'transparent currentcolor inherit initial unset aliceblue antiquewhite aqua aquamarine '
			. 'azure beige bisque black blanchedalmond blue blueviolet brown burlywood cadetblue '
			. 'chartreuse chocolate coral cornflowerblue cornsilk crimson cyan darkblue darkcyan '
			. 'darkgoldenrod darkgray darkgreen darkgrey darkkhaki darkmagenta darkolivegreen '
			. 'darkorange darkorchid darkred darksalmon darkseagreen darkslateblue darkslategray '
			. 'darkslategrey darkturquoise darkviolet deeppink deepskyblue dimgray dimgrey dodgerblue '
			. 'firebrick floralwhite forestgreen fuchsia gainsboro ghostwhite gold goldenrod gray green '
			. 'greenyellow grey honeydew hotpink indianred indigo ivory khaki lavender lavenderblush '
			. 'lawngreen lemonchiffon lightblue lightcoral lightcyan lightgoldenrodyellow lightgray '
			. 'lightgreen lightgrey lightpink lightsalmon lightseagreen lightskyblue lightslategray '
			. 'lightslategrey lightsteelblue lightyellow lime limegreen linen magenta maroon '
			. 'mediumaquamarine mediumblue mediumorchid mediumpurple mediumseagreen mediumslateblue '
			. 'mediumspringgreen mediumturquoise mediumvioletred midnightblue mintcream mistyrose '
			. 'moccasin navajowhite navy oldlace olive olivedrab orange orangered orchid palegoldenrod '
			. 'palegreen paleturquoise palevioletred papayawhip peachpuff peru pink plum powderblue '
			. 'purple rebeccapurple red rosybrown royalblue saddlebrown salmon sandybrown seagreen '
			. 'seashell sienna silver skyblue slateblue slategray slategrey snow springgreen steelblue '
			. 'tan teal thistle tomato turquoise violet wheat white whitesmoke yellow yellowgreen'
		);

		return $colors;
	}

	/**
	 * Validate a settings value used as the numeric part of a CSS length.
	 *
	 * These settings hold a bare number, the unit is a separate setting, so the grammar
	 * is deliberately just a number. The 'inherit' and '0' sentinels are filtered by the
	 * caller before this runs. SMASH-1770.
	 *
	 * @param string $value Raw setting value.
	 * @return string|false The number if valid, false otherwise.
	 */
	public static function css_number( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value || ! preg_match( '/^-?(?:\d+|\d*\.\d+)$/', $value ) ) {
			return false;
		}

		return $value;
	}

	/**
	 * Validate a settings value used as a CSS length unit.
	 *
	 * Only height and width carry a caller-supplied unit (height_unit / width_unit,
	 * both '%' by default); everything else defaults to px. SMASH-1770.
	 *
	 * @param string $unit Raw unit value.
	 * @return string|false The unit if valid, false otherwise.
	 */
	public static function css_unit( $unit ) {
		$unit  = strtolower( trim( (string) $unit ) );
		$units = array( 'px', '%', 'em', 'rem', 'vh', 'vw', 'vmin', 'vmax', 'pt', 'pc', 'ex', 'ch', 'cm', 'mm', 'in' );

		if ( ! in_array( $unit, $units, true ) ) {
			return false;
		}

		return $unit;
	}

	/**
	 * Validate a settings value used as a CSS border style keyword. SMASH-1770.
	 *
	 * @param string $style Raw border style value.
	 * @return string|false The keyword if valid, false otherwise.
	 */
	public static function css_border_style( $style ) {
		$style  = strtolower( trim( (string) $style ) );
		$styles = array(
			'none',
			'hidden',
			'solid',
			'dashed',
			'dotted',
			'double',
			'groove',
			'ridge',
			'inset',
			'outset',
		);

		if ( ! in_array( $style, $styles, true ) ) {
			return false;
		}

		return $style;
	}

}
