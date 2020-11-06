<?php

use Hcode\Page;
use Hcode\Model\Address;
use Hcode\Model\Cart;
use Hcode\Model\Category;
use Hcode\Model\Product;
use Hcode\Model\User;


$app->get('/', function()
{
	$products = Product::listAll();

	$page = new Page();
	$page->setTpl("index", [
		"products"=>Product::checkList($products)
	]);
});

$app->get("/categories/:idcategory", function($idcategory)
{
	$currentPage = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($currentPage);

	$pages = [];

	for ($i=1; $i<=$pagination['pages'] ; $i++)
	{ 
		array_push($pages, [
			'link'=>"/categories/{$category->getidcategory()}?page={$i}",
			'page'=>$i
		]);
	}

	$page = new Page;

	$page->setTpl("category", [
		"category"=>$category->getValues(),
		"products"=>$pagination['data'],
		"pages"=>$pages,
		"currentpage"=>$currentPage,
		"lastpage"=>$pagination['pages']
	]);
});

$app->get("/products/:desurl", function($desurl)
{
	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", [
		"product"=>$product->getValues(),
		"categories"=>$product->getCategories()
	]);
});

/* Carrinho de Compras ------------------------------------------------------- */

$app->get("/cart", function() 
{
	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl('cart', [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);
});


$app->get("/cart/:idproduct/add", function($idproduct) 
{
	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	for ($i=0; $i < $qtd; $i++)
	{ 
		$cart->addProduct($product);
	}

	header("Location: /cart");
	exit;
});

$app->get("/cart/:idproduct/minus", function($idproduct) 
{
	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product);

	header("Location: /cart");
	exit;
});

$app->get("/cart/:idproduct/remove", function($idproduct) 
{
	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;
});

$app->post("/cart/freight", function()
{
	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;
});

$app->get("/checkout", function()
{
	User::verifyLogin(false);

	$address = new Address();
	$cart = Cart::getFromSession();

	if (!isset($_GET['zipcode']))
	{
		$_GET['zipcode'] = $cart->getdeszipcode();
	}

	if(isset($_GET['zipcode']))
	{
		$address->loadFromCEP($_GET['zipcode']);

		$cart->setdeszipcode($_GET['zipcode']);

		$cart->save();

		$cart->getCalculateTotal();
	}

	if(!$address->getdesaddress()) 	  { $address->setdesaddress('');}
	if(!$address->getdescomplement()) { $address->setdescomplement('');}
	if(!$address->getdesdistrict())   { $address->setdesdistrict('');}
	if(!$address->getdescity())       { $address->setdescity('');}
	if(!$address->getdesstate())      { $address->setdesstate('');}
	if(!$address->getdescountry())    { $address->setdescountry('');}
	if(!$address->getdeszipcode())    { $address->setdeszipcode('');}

	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Address::getError()
	]);
});

$app->post("/checkout", function()
{	
	User::verifyLogin(false);

	$address = new Address();
	$user = User::getFromSession();

	if(!isset($_POST['zipcode']) || $_POST['zipcode'] == '')
	{
		Address::setError("Informe o CEP.");
		header("Location: /checkout");
		exit;
	}

	if(!isset($_POST['desaddress']) || $_POST['desaddress'] == '')
	{
		Address::setError("Informe o endereço.");
		header("Location: /checkout");
		exit;
	}

	if(!isset($_POST['desdistrict']) || $_POST['desdistrict'] == '')
	{
		Address::setError("Informe o bairro.");
		header("Location: /checkout");
		exit;
	}

	if(!isset($_POST['descity']) || $_POST['descity'] == '')
	{
		Address::setError("Informe a cidade.");
		header("Location: /checkout");
		exit;
	}

	if(!isset($_POST['desstate']) || $_POST['desstate'] == '')
	{
		Address::setError("Informe o estado.");
		header("Location: /checkout");
		exit;
	}

	if(!isset($_POST['descountry']) || $_POST['descountry'] == '')
	{
		Address::setError("Informe o Pais.");
		header("Location: /checkout");
		exit;
	}

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson']   = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	header("Location: /order");
	exit;
	
});

/* Login de usuário do site ------------------------------------------------------------------ */

$app->get('/login', function()
{
	$page = new Page();

	$page->setTpl("Login", [
		'error'=>User::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : [
			'name' =>'',
			'email'=>'',
			'phone'=>''
		]
	]);
});

$app->post('/login', function()
{
	try
	{
		User::login($_POST['login'], $_POST['password']);
	}
	catch (Exception $e)
	{
		User::setError($e->getMessage());
	}

	header("Location: /checkout");
	exit;

});


$app->get('/logout', function()
{
	User::logout();

	header("Location: /login");
	exit;
});

/* Cadastro de Usuário do Site ------------------------------------------------------- */

$app->post('/register', function()
{

	$_SESSION['registerValues'] = $_POST;

	if(!isset($_POST['name']) || trim($_POST['name']) == '')
	{
		User::setErrorRegister("Preencha o seu nome.");
		header("Location: /login");
		exit;
	}

	if(!isset($_POST['email']) || trim($_POST['email']) == '')
	{
		User::setErrorRegister("Preencha o seu email.");
		header("Location: /login");
		exit;
	}

	if(!isset($_POST['password']) || trim($_POST['password']) == '')
	{
		User::setErrorRegister("Preencha a sua senha.");
		header("Location: /login");
		exit;
	}

	
	if(User::checkLoginExist($_POST['email']) === true)
	{
		User::setErrorRegister("E-mail já está em uso por outro usuário.");
		header("Location: /login");
		exit;
	}

	$user = new User();

	$user->setData([
		'inadmin'	 =>0,
		'deslogin'	 =>$_POST['email'],
		'desperson'  =>$_POST['name'],
		'desemail'   =>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrpohone'   =>$_POST['phone']
	]);

	$user->save();

	if(isset($_SESSION['registerValues']))
	{
		unset($_SESSION['registerValues']);
	}

	User::login($_POST['email'], $_POST['password']);

	header("Location: /checkout");
	exit;
});

/* Recuperar Senha --------------------------------------------------------------------*/

$app->get("/forgot", function() {
	$page = new Page();
	$page->setTpl("forgot");
});

$app->post("/forgot", function() {
	$user = User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit();
});

$app->get("/forgot/sent", function() {
	$page = new Page();

	$page->setTpl("forgot-sent");
});

$app->get("/forgot/reset", function() {

	$user = User::validForgotDecrypt($_GET['code']);

	$page = new Page();

	$page->setTpl("forgot-reset", array(
		"name"=>$user['desperson'],
		"code"=>$_GET["code"]
	));
});

$app->post("/forgot/reset", function() {

	$forgot = User::validForgotDecrypt($_POST['code']);

	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();
	$user->get((int)$forgot['iduser']);

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
		"cost"=>12
	]);

	$user->setPassword($password);

	$page = new Page();

	$page->setTpl("forgot-reset-success");
});


/* Minha Conta ----------------------------------------------------------------- */

$app->get("/profile", function()
{
	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile", [
		'user'=>$user->getValues(),
		'profileMsg'=>User::getSuccess(),
		'profileError'=>User::getError()
	]);
});


$app->post("/profile", function()
{
	User::verifyLogin(false);

	if(!isset($_POST['desperson']) || $_POST['desperson'] === '')
	{
		User::setError("Preencha o seu nome.");
		header("Location: /profile");
		exit;
	}

	if(!isset($_POST['desemail']) || $_POST['desemail'] === '')
	{
		User::setError("Preencha o seu email.");
		header("Location: /profile");
		exit;
	}

	$user = User::getFromSession();

	if($_POST['desemail'] !== $user->getdesemail())
	{
		if(User::checkLoginExist($_POST['desemail']))
		{
			User::setError("Este endereço de e-mail já está cadastrado.");
			header("Location: /profile");
			exit;
		}
	}

	$_POST['inadmin']     = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin']    = $_POST['desemail'];

	$user->setData($_POST);

	$user->update(true);

	//Atualiza variaveis da sessão
	$_SESSION[User::SESSION]['deslogin']  = $_POST['desemail'];
	$_SESSION[User::SESSION]['desperson'] = $_POST['desperson'];
	$_SESSION[User::SESSION]['desemail']  = $_POST['desemail'];
	$_SESSION[User::SESSION]['nrphone']   = $_POST['nrphone'];

	User::setSuccess("Dados alterados com sucesso.");

	header("Location: /profile");
	exit;
});
?>