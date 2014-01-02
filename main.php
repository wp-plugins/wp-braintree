<?php
/**
 * Plugin Name: WP BrainTree
 * Plugin URI: http://www.tipsandtricks-hq.com/wordpress-braintree-plugin
 * Description: Create "Buy Now" buttons for BrainTree payment gateway.
 * Version: 1.0
 * Author: Tips and Tricks HQ, josh401
 * Author URI: http://www.tipsandtricks-hq.com/
 * License: GPL2
*/

class wp_braintree {
	
	// Setup options used for this plugin
	protected $option_name = 'wp_braintree_opts';
	protected $api_keys_name = 'wp_braintree_api_keys';
	// These options will be used for default data
	protected $data = array(
		'api_keys_tab' => array(
			'merchant_id' => '',
			'public_key' => '',
			'private_key' => '',
			'cse_key' => ''
		),
		'opts_tab' => array(
			'sandbox_mode' => '0',
			'auth_only' => '0',
			//'create_customer' => '0',
			'success_url' => '',
			'jq_theme' => 'smoothness'
		)
	);
	
	// Private function for accessing the BrainTree API
	private function wp_braintree_get_api() {
		
		// Get plugin api keys from database
		$api_keys = get_option($this->api_keys_name);
		
		$api_keys_merchant_id = isset($api_keys['merchant_id']) ? $api_keys['merchant_id'] : '';
		$api_keys_public_key = isset($api_keys['public_key']) ? $api_keys['public_key'] : '';
		$api_keys_private_key = isset($api_keys['private_key']) ? $api_keys['private_key'] : '';
		
		// Is this plugin running in sandbox or production?
		$options = get_option($this->option_name);
		$sandbox = $options['sandbox_mode'] == '1' ? 'sandbox' : 'production';
	
		// Include BrainTree library
		require_once 'braintree/lib/Braintree.php';
		
		// Initiate BrainTree
		Braintree_Configuration::environment($sandbox);
		Braintree_Configuration::merchantId($api_keys_merchant_id);
		Braintree_Configuration::publicKey($api_keys_public_key);
		Braintree_Configuration::privateKey($api_keys_private_key);
	}
	
	// Construct the plugin
	public function __construct() {
		
		load_plugin_textdomain( 'wp_braintree_lang', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );  // Load plugin text domain
		
		add_action('admin_init', array($this, 'admin_init'));  // Used for registering settings
		add_action('admin_menu', array($this, 'add_page'));  // Creates admin menu page and conditionally loads scripts and styles on admin page
		add_action('init', array($this, 'wp_braintree_tinymce_button'));  // Create tinymce button
		add_action('wp_enqueue_scripts', array($this, 'head_styles_scripts'));  // Add scripts and styles to frontend (shortcode used to filter posts so it is not added to all)
		add_shortcode('wp_braintree_button', array($this, 'wp_braintree_button_shortcode'));  // Generate shortcode output
		add_filter( 'mce_external_languages', array($this, 'localize_tinymce' ));  // Localize tinymce langs for translation
		register_activation_hook( __FILE__, array($this, 'activate'));  // Activate plugin
	}
	
	// Register Plugin settings array
	public function admin_init() {
		register_setting('wp_braintree_options', $this->option_name, array($this, 'validate_options'));
		register_setting('wp_braintree_api_keys', $this->api_keys_name, array($this, 'validate_api_keys'));
	}
	
	// Validate plugin input fields
	// Currently not used.. so value is set to input
	public function validate_api_keys($input) {
		
		$options = get_option($this->api_keys_name);
		
		$valid = array();
		$valid['merchant_id'] = $input['merchant_id'];
		$valid['public_key'] = $input['public_key'];
		$valid['private_key'] = $input['private_key'];
		$valid['cse_key'] = $input['cse_key'];
		
		return $valid;
	}
	public function validate_options($input) {
		
		$options = get_option($this->option_name);
		
		$valid = array();
		$valid['sandbox_mode'] = isset($input['sandbox_mode']) ? '1' : '0';
		$valid['auth_only'] = isset($input['auth_only']) ? '1' : '0';
		//$valid['create_customer'] = isset($input['create_customer']) ? '1' : '0';
		$valid['success_url'] = isset($input['success_url']) ? esc_url($input['success_url']) : '';
		$valid['jq_theme'] = isset($input['jq_theme']) ? $input['jq_theme'] : 'smoothness';
		
		return $valid;
	}
	
	// Initialize admin page
	public function add_page() {
		$wp_braintree_page = add_options_page(__('WP BrainTree', 'wp_braintree_lang'), __('WP BrainTree', 'wp_braintree_lang'), 'manage_options', 'wp_braintree_options', array($this, 'options_do_page'));
		add_action( 'admin_print_scripts-' . $wp_braintree_page, array( $this, 'wp_braintree_admin_scripts' ) );  // Load our admin page scripts (our page only)
		add_action( 'admin_print_styles-' . $wp_braintree_page, array( $this, 'wp_braintree_admin_styles' ) );  // Load our admin page stylesheet (our page only)
	}
	public function wp_braintree_admin_scripts() {
		wp_enqueue_script('jquery-ui-tabs');  // For admin panel page tabs
		wp_enqueue_script('jquery-ui-dialog');  // For admin panel popup alerts
		wp_enqueue_script( 'wp_braintree_scripts', plugins_url( '/js/admin_page.js', __FILE__ ), array('jquery') );  // Apply admin page scripts
	}
	public function wp_braintree_admin_styles() {	
		wp_enqueue_style('wp_braintree_styles', plugins_url( '/css/admin_page.css', __FILE__ ));  // Apply admin page styles
		?><link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"><?php  // For jquery ui styling - Direct from jquery
	}
	
	// Generate admin options page
	public function options_do_page() {
		
		$options_opts = get_option($this->option_name);
		$options_api = get_option($this->api_keys_name);
		?>
		<div class="wrap">
        	<div id="icon-themes" class="icon32"></div>
			<h2><?php _e('WP BrainTree Options','wp_braintree_lang'); ?></h2>
            
            <?php
			$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'api_keys';
			?>
            
            <h2 class="nav-tab-wrapper">  
                <a href="?page=wp_braintree_options&tab=api_keys" class="nav-tab <?php echo $active_tab == 'api_keys' ? 'nav-tab-active' : ''; ?>">API Keys</a>
                <a href="?page=wp_braintree_options&tab=options" class="nav-tab <?php echo $active_tab == 'options' ? 'nav-tab-active' : ''; ?>">Options</a>
                <a href="?page=wp_braintree_options&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Help</a>
                <a href="?page=wp_braintree_options&tab=active_buttons" class="nav-tab <?php echo $active_tab == 'active_buttons' ? 'nav-tab-active' : ''; ?>">Active Buttons</a> 
            </h2>  
            
            <form method="post" action="options.php">
                
                <?php
				if ($active_tab == 'options') { // OPTIONS TAB
					
					?>
                    <div class="postbox">
						<?php
                        settings_fields('wp_braintree_options'); ?>
                        <h3><?php _e('Additional Options:', 'wp_braintree_lang') ?></h3>
                        <table class="form-table">
                            <tr valign="top"><th scope="row"><?php _e('Sandbox Mode:', 'wp_braintree_lang') ?></th>
                                <td>
                                    <input id="sandbox_mode" type="checkbox" name="<?php echo $this->option_name?>[sandbox_mode]" value="<?php echo $options_opts['sandbox_mode']; ?>" <?php if($options_opts['sandbox_mode']) echo 'checked=checked' ?>/>
                                    <br />
                                    <?php _e('Check to run the plugin in sandbox mode.', 'wp_braintree_lang') ?>
                                </td>
                            </tr>
                            <tr valign="top"><th scope="row"><?php _e('Authorize Only:', 'wp_braintree_lang') ?></th>
                                <td>
                                    <input id="auth_only" type="checkbox" name="<?php echo $this->option_name?>[auth_only]" value="<?php echo $options_opts['auth_only']; ?>" <?php if($options_opts['auth_only']) echo 'checked=checked' ?>/>
                                    <br />
                                    <?php _e('Checking this option processes transactions in an "Authorized" status.', 'wp_braintree_lang') ?>
                                    <br />
                                    <?php _e('Unchecking this option processes transactions in a "Submitted for Settlement" status.', 'wp_braintree_lang') ?>
                                </td>
                            </tr>
                            <!--
                            <tr valign="top"><th scope="row"><?php //_e('Create Customer:', 'wp_braintree_lang') ?></th>
                                <td>
                                    <input id="create_customer" type="checkbox" name="<?php //echo $this->option_name?>[create_customer]" value="<?php //echo $options_opts['create_customer']; ?>" <?php //if($options_opts['create_customer']) echo 'checked=checked' ?>/>
                                    <br />
                                    <?php //_e('Checking this option will create a new customer on each successful transaction.', 'wp_braintree_lang') ?>
                                </td>
                            </tr>
                            -->
                            <tr valign="top"><th scope="row"><?php _e('Success URL:', 'wp_braintree_lang') ?></th>
                                <td>
                                    <input id="success_url" type="text" size="60" name="<?php echo $this->option_name?>[success_url]" value="<?php echo $options_opts['success_url']; ?>" <?php if($options_opts['success_url']) echo 'checked=checked' ?>/>
                                    <br />
                                    <?php _e('Enter a return url for successful transactions (a thank you page).', 'wp_braintree_lang') ?>
                                    <br />
                                    <?php _e('If no url is specified (blank), the user will be redirected to the home page.', 'wp_braintree_lang') ?>
                                </td>
                            </tr>
                            <tr valign="top"><th scope="row"><?php _e('jQuery Theme', 'wp_braintree_lang') ?></th>
                                <td>
                                    <select name="<?php echo $this->option_name?>[jq_theme]"/>
                                        <?php
                                        $jquery_themes = array('base','black-tie','blitzer','cupertino','dark-hive','dot-luv','eggplant','excite-bike','flick','hot-sneaks','humanity','le-frog','mint-choc','overcast','pepper-grinder','redmond','smoothness','south-street','start','sunny','swanky-purse','trontastic','ui-darkness','ui-lightness','vader');
                                        
                                        foreach($jquery_themes as $jquery_theme) {
                                            $selected = ($options_opts['jq_theme']==$jquery_theme) ? 'selected="selected"' : '';
                                            echo "<option value='$jquery_theme' $selected>$jquery_theme</option>";
                                        }
                                        ?>
                                    </select>
                                    <br />
                                    <?php _e('Select jQuery theme used for user notifications.', 'wp_braintree_lang') ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php
				}
				else if ($active_tab == 'help') {  // HELP TAB
                
					?>
                	<div class="postbox">
                        <h3><?php _e('Acquire API Keys' ,'wp_braintree_lang'); ?></h3>
                        <p>
                        <?php _e('It is first necessary to register for an account with <a target="_blank" href="https://www.braintreepayments.com/">BrainTree</a>.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e('Once an account is acquired, the following information can be found by logging in and clicking "Account -> My User -> API Keys".' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e('This plugin is set to run in the BrainTree "Production" environment. If desired, the plugin may be switched to the "Sandbox" environment via the appropriate option.' ,'wp_braintree_lang'); ?>
                        </p>
                        
                        <h3><?php _e('Sandbox Mode' ,'wp_braintree_lang'); ?></h3>
                        <p>
                        <?php _e('By default, this plugin will perform all transactions assuming the API keys are from a BrainTree Live Production Account.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e('The plugin may be switched to perform transactions into a BrainTree Sandbox Account; commonly used for testing.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e('Remember; a BrainTree Production Account and a BrainTree Sandbox Account will have different API keys.' ,'wp_braintree_lang'); ?>
                        </p>
                        
                        <h3><?php _e('Authorize Only' ,'wp_braintree_lang'); ?></h3>
                        <p>
                        <?php _e('By default, this plugin will submit transactions immediatly for settlement as they are transacted.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e('If preferred, the plugin can be switched to perform all transactions as an Authorized status.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e('This means the funds will be authorized by BrainTree, but will not be available until the transaction is manually submitted for settlement.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e('Authorized transactions may be manually submitted for settlement via the BrainTree admin control panel.' ,'wp_braintree_lang'); ?>
                        </p>
                        
                        <!--
                        <h3><?php //_e('Create Customer' ,'wp_braintree_lang'); ?></h3>
                        <p>
                        <?php //_e('By default, this plugin will display a "quick form" asking the customer only for the credit card number, card cvv code and card expiration date.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php //_e('If preferred, the plugin can be switched to create customer accounts on each transaction.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php //_e('This will add additional form fields for the customers first name, last name and postal code.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php //_e('Additional form options may be available in the future. Please ask your administrator to contact us with suggestions.' ,'wp_braintree_lang'); ?>
                        </p>
                        -->
                        
                        <h3><?php _e('jQuery Theme' ,'wp_braintree_lang'); ?></h3>
                        <p>
                        <?php _e('By default, this plugin will use the "Smoothness" jQuery dialog theme when display dialog alerts during the checkout process.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e('Additional jQuery themes are available to help match the look and appearance of the website.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e("If you've never seen them before, take a moment to view a few. It's fun!" ,'wp_braintree_lang'); ?>
                        </p>
                    </div>
                <?php
				} 
				else if ($active_tab == 'active_buttons') {  // ACTIVE BUTTONS TAB
              
			  		?>
                    <div class="postbox">
                        <h3><?php _e('Active Pages Currently Using Shortcode' ,'wp_braintree_lang'); ?></h3>
                        <p>
                        <?php _e('Here is a convenient list of all pages currently using the WP BrainTree button shortcode.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e('The titles link direcly to the permalinks of the pages (new window).' ,'wp_braintree_lang'); ?>
                        </p>
                        
                        <?php
                        // Let's be nice and display all pages using the button shortcode to the admin user
                        $args = array( 's' => '[wp_braintree_button ' );
                        $the_query = new WP_Query( $args );
                        if ( $the_query->have_posts() ) {
                            echo '<ul>';
                                while ( $the_query->have_posts() ) {
                                    $the_query->the_post(); 
                                    ?><li><a target="_blank" href="<?php the_permalink() ?>"><?php the_title(); ?></a></li><?php
                                }
                            echo '</ul>';
                        } else {
                            _e('There are currently no posts using the shortcode.' ,'wp_braintree_lang');
                        }
                        wp_reset_postdata();
						?>
                    </div>
                    <?php
				}
				else {  // API KEYS TAB
					
					?>
                    <div class="postbox">
						<?php
                        settings_fields('wp_braintree_api_keys'); ?>
                        <p>
                        <?php _e('It is first necessary to register for an account with <a target="_blank" href="https://www.braintreepayments.com/">BrainTree</a>.' ,'wp_braintree_lang'); ?>
                        <br />
                        <?php _e('Once an account is acquired, the following information can be found by logging in and clicking "Account -> My User -> API Keys".' ,'wp_braintree_lang'); ?>
                        </p>
                        <table class="form-table">
                            <tr valign="top"><th scope="row"><?php _e('Merchant ID:', 'wp_braintree_lang'); ?></th>
                                <td><input id="merchant_id" type="text" name="<?php echo $this->api_keys_name?>[merchant_id]" value="<?php echo $options_api['merchant_id']; ?>" /></td>
                            </tr>
                            <tr valign="top"><th scope="row"><?php _e('Public Key:', 'wp_braintree_lang'); ?></th>
                                <td><input id="public_key" type="text" name="<?php echo $this->api_keys_name?>[public_key]" value="<?php echo $options_api['public_key']; ?>" /></td>
                            </tr>
                            <tr valign="top"><th scope="row"><?php _e('Private Key:', 'wp_braintree_lang'); ?></th>
                                <td><input id="private_key" type="text" name="<?php echo $this->api_keys_name?>[private_key]" value="<?php echo $options_api['private_key']; ?>" /></td>
                            </tr>
                            <tr valign="top"><th scope="row"><?php _e('CSE Key:', 'wp_braintree_lang'); ?></th>
                                <td><textarea id="cse_key" type="text" name="<?php echo $this->api_keys_name?>[cse_key]" value="<?php isset($options_api['cse_key']) ? $options_api['cse_key'] : '';?>"/><?php echo $options_api['cse_key']; ?></textarea></td>
                            </tr>
                        </table>
                    </div>
                 <?php
				}
				?>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Changes', 'wp_braintree_lang') ?>" />
                </p>
            </form>
            
            
		</div> <!-- End wrap -->
        <?php
	}
	
	// Localize tinymce button and popup language strings
	public function localize_tinymce() {
		
		$arr[] = WP_CONTENT_DIR . '/plugins/wp_braintree/lang/mce_lang.php';
    	return $arr;
	}
	
	// This function gets loaded into the HEAD of any post/page using the shortcode
	public function head_styles_scripts() {
		
		global $post;
		if ( strstr( $post->post_content, '[wp_braintree_button ' ) ) {  // Used to identify post content with shortcode usage
			
			$opts = get_option($this->option_name);
			$opts_api = get_option($this->api_keys_name);
			
			$cse_key = $opts_api['cse_key'];
			$select_theme = isset($opts['jq_theme']) ? $opts['jq_theme'] : 'smoothness';
			$success_url = (isset($opts['success_url']) && !empty($opts['success_url'])) ? $opts['success_url'] : home_url();
		
			$this->wp_braintree_get_api();  // Call braintree api above
			
			// Enqueue scripts and styles on front page (only if they are not already called)
			if( !wp_script_is('jquery') ) { 
				wp_enqueue_script('jquery');
			}
			if( !wp_script_is('jquery-ui-dialog') ) { 
				wp_enqueue_script('jquery-ui-dialog');
			}
			wp_enqueue_style('wp_braintree_styles_front', plugins_url( '/css/front_page.css', __FILE__ ));    // Apply frontend styles
			wp_enqueue_script( 'wp_braintree_scripts_front', plugins_url( '/js/front_page.js', __FILE__ ), array('jquery') );  // Apply frontend scripts
			// Localize js langs
			wp_localize_script( 'wp_braintree_scripts_front', 'wp_braintree_scripts_front_js_vars', array( 
				'ajaxurl' => Braintree_TransparentRedirect::url(),
				'success_url' => $success_url, 
				'cse_key' => $cse_key,
				'cc_no_valid' => __('The Card Number is not a valid number.', 'wp_braintree_lang'),
				'cc_digits' => __('The Card Number must be between nine (9) and sixteen (16) digits.', 'wp_braintree_lang'),
				'cvv_number' => __('The CVV Number is not a valid number.', 'wp_braintree_lang'),
				'cvv_digits' => __('The CVV Number must be three (3) digits exactly.', 'wp_braintree_lang'),
				'exp_month_number' => __('The Expiration Month is not a valid number.', 'wp_braintree_lang'),
				'exp_month_digits' => __('The Expiration Month must be two (2) digits exactly.', 'wp_braintree_lang'),
				'exp_year_number' => __('The Expiration Year is not a valid number.', 'wp_braintree_lang'),
				'exp_year_digits' => __('The Expiration Year must be four (4) digits exactly.', 'wp_braintree_lang'),
				'val_errors' => __('Validation Errors:', 'wp_braintree_lang'),
				'confirm_trans' => __('You are about to submit this transaction. Continue?', 'wp_braintree_lang')
				));
			?>
            <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/<?php echo $select_theme ?>/jquery-ui.css">  <!-- For jquery ui styling - Direct from jquery -->
        	<script type="text/javascript" src="https://js.braintreegateway.com/v1/braintree.js"></script>  <!-- For BrainTree api -->
        	<?php
			
			// If a payment has been submitted, the page MUST be reloaded.
			// This if statement determines if the page load includes a response from the payment gateway
			if(isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
				
				// Setup query string to be processed by braintree
				// This string gets added to the redirect url (has to be current WP post/page displaying the form)
				$queryString = $_SERVER['QUERY_STRING'];
				
				// The result will contain the braintree response (deciphered from query string)
				$result = Braintree_TransparentRedirect::confirm($queryString);
				
				// Find out if user wants to authorize only
				// If not, we are going to submit the transaction for settlement immediately
				$auth_only = $opts['auth_only'] == '1' ? 'yes' : 'no';
				if($auth_only == 'no') {
					// Submit transaction for settlement
					$result = Braintree_Transaction::submitForSettlement($result->transaction->id);
				}
				
				if ($result->success) {
					echo("<div id='dialog-message-success' title='".__("Purchase Success!", "wp_braintree_lang")."'>");
						echo(__("Congratulations! The transaction has completed successfully. Please keep the Transaction ID for your records.", "wp_braintree_lang"));
						echo("<br /><br />");
						echo("<strong>".__("Transaction ID:", "wp_braintree_lang")." </strong>" . $result->transaction->id);
					echo("</div>");
				} 
				// I have no idea what this is for.  I'm assuming it contains output if the braintree server encounters an error.
				// I have not come across a situation where this response was available.
				else if ($result->transaction) {
					echo("<div id='dialog-message-error' title='".__("Transaction Error", "wp_braintree_lang")."'>");
						echo(__("Error: ", "wp_braintree_lang") . $result->message);
						echo("<br/>");
						echo(__("Code: ", "wp_braintree_lang") . $result->transaction->processorResponseCode);
						echo("<br /><br />");
						echo(__("Please use the browsers 'back' button to verify the input fields, and try again.", "wp_braintree_lang"));
					echo("</div>");
				} 
				// Else the transaction contains validation errors
				else {
					echo("<div id='dialog-message-error' title='".__("Validation Error", "wp_braintree_lang")."'>");
						echo(__("Validation errors:<br/>", "wp_braintree_lang"));
						foreach (($result->errors->deepAll()) as $error) {
							echo("- " . $error->message . "<br/>");
						}
						echo("<br />".__("No transaction was submitted for processing.", "wp_braintree_lang"));
						echo("<br />");
						echo(__("Please press the 'Back' button to verify the input fields, and re-submit.", "wp_braintree_lang"));
					echo("</div>");
				}
					
			} // End - if server query (payment was submitted for processing)
		} // End - if content has shortcode button
	} // End public function
	
	// Output the shortcode
	public function wp_braintree_button_shortcode($atts) {
	
		$opts = get_option($this->option_name);
		
		// Extract shortcode args
		extract(shortcode_atts(array( 'item_name' => 'item_name', 'item_amount' => 'item_amount'), $atts));
		
		// Call braintree api above
		$this->wp_braintree_get_api();
		
		// Get url of current page (used for the redirect - which adds a hash to the current page - which MUST be read from the redirect url)
		$cur_page = $this->curPageURL();
		
		// Setup protected table data for the price of the item.
		// This prevents tampering of the price via the browser.
		// Other values may be passed (and protected) by adding to this array
		$trData = Braintree_TransparentRedirect::transactionData(
		  array(
			'transaction' => array(
			  'type' => Braintree_Transaction::SALE,
			  'amount' => $item_amount
			),
			'redirectUrl' => $cur_page
		  )
		);
		
		// Begin shortcode div
		$button_form = '';
		$button_form .= '<div class="wp_braintree_button">';
		
			// Create buy now button
			$button_form .= '<div class="wp_braintree_button_div">';
				$button_form .= '<form name="wp_braintree_button_submit" action="" method="POST">';
					$button_form .= '<input type="hidden" name="item_name" value="'.$item_name.'" />';
					$button_form .= '<input type="hidden" name="item_amount" value="'.$item_amount.'" />';
					$button_form .= '<input type="button" value="'.__('Buy Now!', 'wp_braintree_lang').'" name="submit_button_form" class="submit_buy_now" />';
				$button_form .= '</form>';
			$button_form .= '</div>';
			
			// Generate the associated form output for payment processing
			// These are hidden on page load via jquery.
			// Clicking the button[code] above.. shows/hides the associated form.
			
			
			$button_form .= '<div class="dialog-form" title="New Transaction">
									<h3>'.__('Braintree Credit Card Transaction Form', 'wp_braintree_lang').'</h3>
									<form action="'.Braintree_TransparentRedirect::url().'" method="POST" class="braintree-payment-form">
									  <table>
									  <tbody>
									  <tr><td>
										'.__('Item Name', 'wp_braintree_lang').'
									  </td><td>
										<span name="item_name_form">'.$item_name.'</span>
									  </td></tr>
									  <tr><td>
										'.__('Item Price', 'wp_braintree_lang').'
									  </td><td>
										<span name="item_price_form">'.$item_amount.'</span>
										<input type="hidden" name="tr_data" value="'.htmlentities($trData).'" />
									  </td></tr>
									  <tr><td>
										<label>'.__('Card Number', 'wp_braintree_lang').'</label>
									  </td><td>
										<input class="number" type="text" name="transaction[credit_card][number]" />
									  </td></tr>
									  <tr><td>
										<label>'.__('CVV', 'wp_braintree_lang').'</label>
									  </td><td>
										<input class="cvv" type="text" name="transaction[credit_card][cvv]" />
									  </td></tr>
									  <tr><td>
										<label>'.__('Expiration (MM/YYYY)', 'wp_braintree_lang').'</label>
									  </td><td>
										<input class="expiration_month" type="text" name="transaction[credit_card][expiration_month]" /> / <input class="expiration_year" type="text" name="transaction[credit_card][expiration_year]" />
									  </td></tr>
									  <tr><td colspan="2">
									  <input type="submit" class="submit_wp_braintree" name="wp_braintree_submit" />
									  </td></tr>
									  </tbody>
									  </table>
									</form>
								</div>';
		$button_form .= '</div>';
		
		return $button_form;
	}
	
	// This function will populate the options if the plugin is activated for the first time.
	// It will also protect the options if the plugin is deactivated (common in troublshooting WP related issues)
	// We may want to add an option to remove DB entries...
	public function activate() {
		
		$options = get_option($this->option_name);
		$options_api = get_option($this->api_keys_name);
		
		$options_api['merchant_id'] = isset($options_api['merchant_id']) ? $options_api['merchant_id'] : '';
		$options_api['public_key'] = isset($options_api['public_key']) ? $options_api['public_key'] : '';
		$options_api['private_key'] = isset($options_api['private_key']) ? $options_api['private_key'] : '';
		$options_api['cse_key'] = isset($options_api['cse_key']) ? $options_api['cse_key'] : '';
		
		$options['sandbox_mode'] = isset($options['sandbox_mode']) ? $options['sandbox_mode'] : '0';
		$options['auth_only'] = isset($options['auth_only']) ? $options['auth_only'] : '0';
		//$options['create_customer'] = isset($options['create_customer']) ? $options['create_customer'] : '0';
		$options['success_url'] = isset($options['success_url']) ? $options['success_url'] : '';
		$options['jq_theme'] = isset($options['jq_theme']) ? $options['jq_theme'] : 'smoothness';
		
		update_option($this->option_name, $options);
		update_option($this->api_keys_name, $options_api);
	}
	
	// Add the tinymce buttton and js file
	public function wp_braintree_tinymce_button() {

	   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
		  return;
	   }
	   if ( get_user_option('rich_editing') == 'true' ) {
		  add_filter( 'mce_external_plugins', array($this, 'add_plugin' ));
		  add_filter( 'mce_buttons', array($this, 'register_button' ));
	   }
	
	}
	
	// Add tinymce button js file
	public function add_plugin( $plugin_array ) {
	   $plugin_array['wp_braintree'] = plugins_url( '/js/editor_plugin.js', __FILE__ );
	   return $plugin_array;
	}
	
	// Add tinymce button to editor
	public function register_button( $buttons ) {
	   array_push( $buttons, "|", "wp_braintree" );
	   return $buttons;
	}
	
	// Function used to get current page url
	public function curPageURL() {
		
		$pageURL = 'http';
		$is_https = isset($_SERVER["HTTPS"]) ? $_SERVER["HTTPS"] : '';
		
		if ($is_https == "on") {
			$pageURL .= "s";
		}
		$pageURL .= "://";
		
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		
		return $pageURL;
	}
	
	// Used for tabbed content
	public function wp_braintree_admin_tabs( $current = 'api_keys' ) {
		
		$tabs = array( 'api_keys' => 'API Keys', 'options' => 'Options', 'help' => 'Help', 'active_buttons' => 'Active Buttons' );
		echo '<div id="icon-themes" class="icon32"><br></div>';
		echo '<h2 class="nav-tab-wrapper">';
		foreach( $tabs as $tab => $name ){
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';
			echo "<a class='nav-tab$class' href='?page=wp_braintree_options&tab=$tab'>$name</a>";
	
		}
		echo '</h2>';
	}
}
$wp_braintree = new wp_braintree();
