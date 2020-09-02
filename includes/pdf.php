<?php

use Spipu\Html2Pdf\Html2Pdf;


class dy_PDF {

	public static function lang()
	{
		return substr(get_locale(), 0, -3);
	}
	public static function get_terms_conditions_pages()
	{		
		$output = array();
		$terms_conditions = dy_Public::get_terms_conditions(sanitize_text_field($_POST['post_id']));

		if(is_array($terms_conditions))
		{
			if(count($terms_conditions) > 0)
			{
				for($x = 0; $x < count($terms_conditions); $x++ )
				{
					$number = $x + 1;
					$name = $terms_conditions[$x]->name;
					
					//PAGE
					$page = '<page backcolor="#ffffff" style="font-size: 12pt;" backtop="10mm" backbottom="10mm" backleft="10mm" backright="10mm">';
					$page .= '<h1 style="text-align: center; margin: 0; padding: 0; font-size: 20pt;">'.esc_html($name).'</h1>';
					$page .= wpautop($terms_conditions[$x]->description);
					$page .= '<page_footer>'.esc_html($name).'</page_footer>';
					$page .= '</page>';		
					
					//PDF
					$pdf = new Html2Pdf('P', 'A4', self::lang());
					$pdf->pdf->SetDisplayMode('fullpage');
					$pdf->writeHTML($page);
		
					//OUTPUT
					$filename = $name . '.pdf';
					
					$output[] = array(
						'filename' => $filename,
						'data' => $pdf->output($filename, 'S')
					);
				}		
			}
		}
		
		return $output;
	}
	

}

?>