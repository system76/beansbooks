<?php defined('SYSPATH') or die('No direct access allowed.');

class View_PDF {

	protected $_pdf;

	public function __construct()
	{
		require_once Kohana::find_file('vendor', 'fpdf/fpdf', 'php');

		$this->_pdf = new FPDF('P','mm','Letter');

		$this->_pdf->AliasNbPages();
		$this->_pdf->SetFont('Courier','',10);
		$this->_pdf->setMargins(2,0,0);
		$this->_pdf->SetAutoPageBreak(FALSE);
	}

	public function render($name = 'doc.pdf', $dest = "I")
	{
		return $this->_pdf->Output($name, $dest);
	}

	// Copy over any necessary helpers from View_Print
	protected function _country_name($code) {
		return Helper_Address::CountryName($code);
	}

	protected function _skip_lines($count, $line_height = 4)
	{
		for( $i = 0; $i < $count; $i++ )
		{
			$this->_pdf->Cell(0,10, '',0,0);
			$this->_pdf->Ln($line_height);
		}
	}

}