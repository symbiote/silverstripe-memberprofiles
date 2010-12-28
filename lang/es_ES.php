<?php
/**
 * Spanish (Spain) language pack.
 *
 * @author     Ricardo Cardona <ricardocardona@hotmail.com>
 * @package    memberprofiles
 * @subpackage i18n
 */

i18n::include_locale_file('memberprofiles', 'en_US');

global $lang;

if(array_key_exists('es_ES', $lang) && is_array($lang['es_ES'])) {
	$lang['es_ES'] = array_merge($lang['en_US'], $lang['es_ES']);
} else {
	$lang['es_ES'] = $lang['en_US'];
}

$lang['es_ES']['MemberProfileField']['PLURALNAME'] = array(
	'Campos del Perfil del Miembro',
	50,
	'Nombre del objeto en plural, usado en las listas y generalmente identifica una coleccion de este objeto en la interface'
);
$lang['es_ES']['MemberProfileField']['SINGULARNAME'] = array(
	'Campo del perfil del miembro',
	50,
	'Nombre del objeto en singular, usado en las listas y generalmente identifica un único objeto en la interface'
);
$lang['es_ES']['MemberProfilePage']['PLURALNAME'] = array(
	'Páginas del Perfil del Miembro',
	50,
	'Nombre del objeto en plural, usado en las listas y generalmente identifica una coleccion de este objeto en la interface'
);
$lang['es_ES']['MemberProfilePage']['SINGULARNAME'] = array(
	'Página del Perfil del Miembro',
	50,
	'Nombre del objeto en singular, usado en las listas y generalmente identifica un único objeto en la interface'
);

$lang['es_ES']['MemberProfiles']['AFTERRED'] = 'Después de la inscripción';
$lang['es_ES']['MemberProfiles']['REDIRECT_AFTER_REG'] = 'Redireccionar después de la inscripción?';
$lang['es_ES']['MemberProfiles']['REDIRECT_TARGET'] = 'Redireccionar a la página';
$lang['es_ES']['MemberProfiles']['ALLOWREG'] = 'Permite el registro a través de esta página';
$lang['es_ES']['MemberProfiles']['CANNOTCONFIRMLOGGEDIN'] = 'No se puede confirmar la cuenta mientras se está conectado en el sistema';
$lang['es_ES']['MemberProfiles']['CANNOTREGPLEASELOGIN'] = 'Usted no puede registrarse en esta página de perfil. Por favor, identifíquese para editar su perfil.';
$lang['es_ES']['MemberProfiles']['CONFIRMCONTENT'] = 'Confirmación de Contenido';
$lang['es_ES']['MemberProfiles']['CONFIRMNOTE'] = 'Este contenido se muestra cuando el usuario confirma su cuenta.';
$lang['es_ES']['MemberProfiles']['CONTENT'] = 'Contenido';
$lang['es_ES']['MemberProfiles']['DEFAULTVALUE'] = 'Valor predeterminado';
$lang['es_ES']['MemberProfiles']['EMAILCONFIRMATION'] = 'Correo de confirmación';
$lang['es_ES']['MemberProfiles']['EMAILFROM'] = 'Coreo de';
$lang['es_ES']['MemberProfiles']['EMAILTEMPLATE'] = 'Plantilla de correo';
$lang['es_ES']['MemberProfiles']['EMAILVALID'] = 'Requiere correo de validación';
$lang['es_ES']['MemberProfiles']['EMAILVALIDATION'] = 'Correo de Validación';
$lang['es_ES']['MemberProfiles']['FIELDOPTIONS'] = 'Opciones del campo';
$lang['es_ES']['MemberProfiles']['GROUPSETTINGS'] = 'Configuración del grupo';
$lang['es_ES']['MemberProfiles']['GROUPSNOTE'] = '<p>Cualquier registro de usuarios a través de esta página se añadirá a los siguientes grupos (si el registro está habilitado). Por el contrario, los miembros deberán pertenecer a estos grupos con el fin de editar su perfil en esta página</p>';
$lang['es_ES']['MemberProfiles']['LOGIN'] = 'Si ya tienes una cuenta, puede iniciar sesión <a href="%s">aquí</a>.';
$lang['es_ES']['MemberProfiles']['MANUALLYCONFIRM'] = 'Confirmar manualmente';
$lang['es_ES']['MemberProfiles']['MEMBERFIELD'] = 'Campo del Miembro';
$lang['es_ES']['MemberProfiles']['MEMBERWITHSAME'] = 'Ya existe un miembro con el mismo %s.';
$lang['es_ES']['MemberProfiles']['NEEDSVALIDATIONTOLOGIN'] = 'Debe validar su cuenta antes de poder entrar';
$lang['es_ES']['MemberProfiles']['NOLOGINTILLCONFIRMED'] = 'El usuario no puede iniciar sesión hasta que su cuenta sea confirma';
$lang['es_ES']['MemberProfiles']['PAGESETTINGS'] = 'Configuración de la página';
$lang['es_ES']['MemberProfiles']['PROFILE'] = 'Perfil';
$lang['es_ES']['MemberProfiles']['PROFILEREGFIELDS'] = 'Campos de Perfil/Registro';
$lang['es_ES']['MemberProfiles']['PROFILEUPDATED'] = 'Su perfil ha sido actualizado.';
$lang['es_ES']['MemberProfiles']['REGISTER'] = 'Registro';
$lang['es_ES']['MemberProfiles']['REGISTRATION'] = 'Registro';
$lang['es_ES']['MemberProfiles']['REGSETTINGS'] = 'Configuración de registro';
$lang['es_ES']['MemberProfiles']['RESEND'] = 'Volver a enviar correo electrónico de confirmación';
$lang['es_ES']['MemberProfiles']['SAVE'] = 'Guardar';
$lang['es_ES']['MemberProfiles']['TITLE'] = 'Título';
$lang['es_ES']['MemberProfiles']['UNCONFIRMED'] = 'Sin confirmar';
$lang['es_ES']['MemberProfiles']['VALIDATION'] = 'Validación';
$lang['es_ES']['MemberProfiles']['VALIDEMAILSUBJECT'] = 'Asunto del correo electrónico de validación';
$lang['es_ES']['MemberProfiles']['VALIDOPTIONS'] = 'Opciones de validación';

$lang['es_ES']['OrderableCTF.ss']['ADDITEM'] = array(
	'Adicionar %s',
	PR_MEDIUM,
	'Adicionar [nombre]'
);

$lang['es_ES']['OrderableCTF.ss']['CSVEXPORT'] = 'Exportar a CSV';
$lang['es_ES']['OrderableCTF.ss']['NOITEMSFOUND'] = 'No se encontraron elementos ';
$lang['es_ES']['OrderableCTF.ss']['SORTASC'] = 'Ordenar ascendente';
$lang['es_ES']['OrderableCTF.ss']['SORTDESC'] = 'Orden descendente';
$lang['es_ES']['TableListField_PageControls.ss']['DISPLAYING'] = 'Mostrar';
$lang['es_ES']['TableListField_PageControls.ss']['OF'] = 'de';
$lang['es_ES']['TableListField_PageControls.ss']['TO'] = 'a';
$lang['es_ES']['TableListField_PageControls.ss']['VIEWFIRST'] = 'Ver los primeros';
$lang['es_ES']['TableListField_PageControls.ss']['VIEWLAST'] = 'Ver el último';
$lang['es_ES']['TableListField_PageControls.ss']['VIEWNEXT'] = 'Ver el siguiente';
$lang['es_ES']['TableListField_PageControls.ss']['VIEWPREVIOUS'] = 'Vista previa';
