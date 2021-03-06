<?php

class tr_seo_plugin {

  function setup() {
    if( !defined('WPSEO_URL') && !defined('AIOSEOP_VERSION') ) {
      define('TR_SEO', '1.0');
      add_action('admin_init', array($this, 'css'));
      add_filter( 'wp_title', array($this, 'title'), 100, 3 );
      add_action( 'wp_head' , array($this, 'head_data'), 0);
      add_action( 'add_meta_boxes', array($this, 'seo_meta') );
      remove_action('wp_head', 'rel_canonical');
      add_action( 'wp', array($this, 'redirect'), 99, 1 );
    }
  }

  function seo_meta() {
    $publicTypes = get_post_types( array( 'public' => true ) );
    $obj = new tr_meta_box();
    $obj->make('tr_seo', array('label' => 'Search Engine Optimization','priority' => 'low'))->apply($publicTypes)->bake();
  }

  // Page Title
  function title( $title, $sep = '', $other = '' ) {
    global $paged, $page;

    $newTitle = tr_post_field('[seo][meta][title]');

    if ( $newTitle != '') {
      if(is_feed() || is_single() || is_page() || is_singular() ) {
        return $newTitle;
      } else {
        return $title;
      }
    } else {
      return $title;
    }

  }

  // head meta data
  function head_data() {
    global $post;

    // meta vars
    $desc = esc_attr(tr_post_field('[seo][meta][description]'));
    $og_title = esc_attr(tr_post_field('[seo][meta][og_title]'));
    $og_desc = esc_attr(tr_post_field('[seo][meta][og_desc]'));
    $img = esc_attr(tr_post_field('[seo][meta][meta_img]'));
    $canon = esc_attr(tr_post_field('[seo][meta][canonical]'));
    $robots['index'] = esc_attr(tr_post_field('[seo][meta][index]'));
    $robots['follow'] = esc_attr(tr_post_field('[seo][meta][follow]'));

    // Extra
    if( !empty( $canon ) ) { echo "<link rel=\"canonical\" href=\"{$canon}\" />"; }
    else { rel_canonical(); }

    // Robots
    if( !empty( $robots ) ) {
      $robot_data = '';
      foreach($robots as $value) {
        if(!empty($value)) {
          $robot_data .= $value . ', ';
        }
      }

      $robot_data = substr($robot_data, 0, -2);
      if(!empty($robot_data)) { echo "<link name=\"robots\" content=\"{$robot_data}\" />"; }
    }

    // OG
    if( !empty( $og_title ) ) { echo "<meta property=\"og:title\" content=\"{$og_title}\" />"; }
    if( !empty( $og_desc ) ) { echo "<meta property=\"og:description\" content=\"{$og_desc}\" />"; }
    if( !empty( $img ) ) { echo "<meta property=\"og:image\" content=\"{$img}\" />"; }

    // Basic
    if( !empty( $desc ) ) { echo "<meta name=\"Description\" content=\"{$desc}\" />"; }
  }

  // 301 Redirect
  function redirect() {
    if ( is_singular() ) {
      $redirect = tr_post_field('[seo][meta][redirect]');
      if ( !empty( $redirect ) ) {
        wp_redirect( $redirect, 301 );
        exit;
      }
    }
  }

  // CSS
  function css() {
    $path = tr::$paths['urls']['plugins'] . '/seo/';
    wp_enqueue_style('tr-seo', $path . 'style.css' );
    wp_enqueue_script('tr-seo', $path . 'script.js' );
  }

}

$tr_seo = new tr_seo_plugin();
add_action('typerocket_loaded', array($tr_seo, 'setup'));
unset($tr_seo);

// build metabox interface
function add_meta_content_tr_seo() {

  $utility = new tr_utility();

  // field settings
  $title = array(
    'label' => 'Page Title'
  );

  $desc = array(
    'label' => 'Search Result Description'
  );

  $og_title = array(
    'label' => 'Title',
    'help' => 'The open graph protocol is used by social networks like FB, Google+ and Pinterest. Set the title used when sharing.'
  );

  $og_desc = array(
    'label' => 'Description',
    'help' => 'Set the open graph description to override "Search Result Description". Will be used by FB, Google+ and Pinterest.'
  );

  $img = array(
    'label' => 'Image',
    'help' => 'The image is shown when sharing socially using the open graph protocol. Will be used by FB, Google+ and Pinterest.'
  );

  $canon = array(
    'label' => 'Canonical URL',
    'help' => 'The canonical URL that this page should point to, leave empty to default to permalink.'
  );

  $redirect = array(
    'label' => '301 Redirect',
    'help' => 'Move this page permanently to a new URL. <a href="#tr_redirect" id="tr_redirect_lock">Unlock 301 Redirect</a>',
    'readonly' => true
  );

  $follow = array(
    'label' => 'Robots Follow?',
    'desc' => "Don't Follow",
    'help' => 'This instructs search engines not to follow links on this page. This only applies to links on this page. It\'s entirely likely that a robot might find the same links on some other page and still arrive at your undesired page.'
  );

  $help = array(
    'label' => 'Robots Index?',
    'desc' => "Don't Index",
    'help' => 'This instructs search engines not to show this page in its web search results.'
  );

  // select options
  $follow_opts = array(
    'Not Set' => '',
    'Follow' => 'follow',
    "Don't Follow" => 'nofollow'
  );

  $index_opts = array(
    'Not Set' => '',
    'Index' => 'index',
    "Don't Index" => 'noindex'
  );

  // build form
  $form = new tr_form();
  $form->group = '[seo][meta]';
  $form->make();
  $utility->buffer();
  $form->text('title', array(), $title)
    ->textarea('description',array(), $desc);
    $utility->buffer('general'); // index buffer
    $utility->buffer();
  $form->text('og_title',array(), $og_title)
    ->textarea('og_desc',array(), $og_desc)
    ->image('meta_img',array(), $img);
    $utility->buffer('social'); // index buffer
    $utility->buffer();
  $form->text('canonical',array(), $canon)
    ->text('redirect',array('readonly' => 'readonly'), $redirect)
    ->select('follow', $follow_opts,array(), $follow)
    ->select('index', $index_opts,array(), $help);
    $utility->buffer('extra'); // index buffer

  $tabs = new tr_layout();
  $tabs->add_tab( array(
      'id' => 'seo-general',
      'title' => "Basic",
      'content' => $utility->buffer['general'],
      'callback' => 'general_cb'
    ) )
    ->add_tab( array(
      'id' => 'seo-social',
      'title' => "OG",
      'content' => $utility->buffer['social']
    ) )
    ->add_tab( array(
      'id' => 'seo-extra',
      'title' => "Extras",
      'content' => $utility->buffer['extra']
    ) )
    ->make('meta');

}

function general_cb() {
  global $post; ?>
  <div id="tr-seo-preview" class="control-group">
    <h4>Example Preview</h4>
    <p>Google has <b>no definitive character limits</b> for page "Titles" and "Descriptions". Because of this there is no way to provide an accurate preview. But, your Google search result may look something like:</p>
    <div class="tr-seo-preview-google">
        <span style="display: none" id="tr-seo-preview-google-title-orig">
          <?php echo substr(strip_tags($post->post_title), 0, 59); ?>
        </span>
        <span id="tr-seo-preview-google-title">
          <?php
          $title = tr_post_field('[seo][meta][title]');
          if(!empty($title)) {
            $s = strip_tags($title);
            $tl = strlen($s);
            echo substr($s, 0, 59);
          } else {
            $s = strip_tags($post->post_title);
            $tl = strlen($s);
            echo substr($s, 0, 59);
          }

          if($tl > 59) {
            echo '...';
          }
          ?>
        </span>
      <div id="tr-seo-preview-google-url">
        <?php echo get_permalink($post->ID); ?>
      </div>
        <span style="display: none" id="tr-seo-preview-google-desc-orig">
          <?php echo substr(strip_tags($post->post_content), 0, 150); ?>
        </span>
        <span id="tr-seo-preview-google-desc">
          <?php
          $desc = tr_post_field('[seo][meta][description]');
          if(!empty($desc)) {
            $s = strip_tags($desc);
            $dl = strlen($s);
            echo substr($s, 0, 150);
          } else {
            $s = strip_tags($post->post_content);
            $dl = strlen($s);
            echo substr($s, 0, 150);
          }

          if($dl > 150) {
            echo ' ...';
          }
          ?>
        </span>
    </div>
  </div>
<?php }