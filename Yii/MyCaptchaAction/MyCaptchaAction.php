<?php

/**
 * Class MyCaptchaAction
 *
 *	Class to draw captcha with cyrillic symbols
 */
class MyCaptchaAction extends CCaptchaAction
{
	public $backend = 'imagick';

	public function generateVerifyCode()
	{
		if($this->minLength > $this->maxLength)
			$this->maxLength = $this->minLength;
		if($this->minLength < 3)
			$this->minLength = 3;
		if($this->maxLength > 20)
			$this->maxLength = 20;
		$length = mt_rand($this->minLength,$this->maxLength);

		$letters = 'гджклмнпрстфхц';
		$vowels = 'аеиуя';

		$code = '';
		for($i = 0; $i < $length; ++$i)
		{
			if($i % 2 && mt_rand(0,10) > 2 || !($i % 2) && mt_rand(0,10) > 9)
				$code.= mb_substr($vowels, mt_rand(0,4), 1, 'UTF-8');
			else
				$code.= mb_substr($letters, mt_rand(0,13), 1, 'UTF-8');
		}

		return $code;
	}

	protected function renderImageImagick($code)
	{
		$backColor=$this->transparent ? new ImagickPixel('transparent') : new ImagickPixel(sprintf('#%06x',$this->backColor));
		$foreColor=new ImagickPixel(sprintf('#%06x',$this->foreColor));

		$image=new Imagick();
		$image->newImage($this->width,$this->height,$backColor);

		if($this->fontFile===null)
		{
			//$this->fontFile=dirname(__FILE__).'/SpicyRice.ttf';
			throw new CHttpException(404, 'No font specified');
		}

		$draw=new ImagickDraw();
		$draw->setFont($this->fontFile);
		$draw->setFontSize(30);
		$fontMetrics=$image->queryFontMetrics($draw,$code);

		//$length=strlen($code);
		$length= mb_strlen($code, 'UTF-8');
		$w=(int)($fontMetrics['textWidth'])-8+$this->offset*($length-1);
		$h=(int)($fontMetrics['textHeight'])-8;
		$scale=min(($this->width-$this->padding*2)/$w,($this->height-$this->padding*2)/$h);
		$x=10;
		$y=round($this->height*27/40);
		for($i=0; $i<$length; ++$i)
		{
			$draw=new ImagickDraw();
			$draw->setFont($this->fontFile);
			$draw->setFontSize((int)(rand(26,32)*$scale*0.8));
			$draw->setFillColor($foreColor);
			$letter = mb_substr($code, $i, 1, 'UTF-8');
			$image->annotateImage($draw,$x,$y,rand(-10,10),$letter);
			$fontMetrics=$image->queryFontMetrics($draw,$letter);
			$x+=(int)($fontMetrics['textWidth'])+$this->offset;
		}

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Transfer-Encoding: binary');
		header("Content-Type: image/png");
		$image->setImageFormat('png');
		echo $image;
	}

	public function renderImage($code)
	{
		if($this->backend===null && CCaptcha::checkRequirements('imagick') || $this->backend==='imagick')
		{
			$this->renderImageImagick($code);
		}
		else
		{
			throw new CHttpException(500, 'No imagick found');
		}
	}

}