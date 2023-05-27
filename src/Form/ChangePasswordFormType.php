<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{PasswordType, RepeatedType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\{Length, NotBlank, Regex};

/**
 * Defines the form used to change the user password
 *
 * @author Pietro Agazzi <agazzi_pietro@protonmail.com>
 */
class ChangePasswordFormType extends AbstractType
{
	/**
	 * @inheritDoc
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add(
				'plainPassword', RepeatedType::class, [
					'type' => PasswordType::class,
					'options' => [
						'attr' => [
							'autocomplete' => 'off',
						],
					],
					'first_options' => [
						'constraints' => [
							new NotBlank(
								message: 'The password cannot be blank',
							),
							new Length(
								min: 8,
								// max length allowed by Symfony for security reasons
								max: 4096,
								minMessage: 'Your password should be at least {{ limit }} characters'
							),
							new Regex(
								pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/',
								message: 'Your password should contain at least one uppercase letter, one lowercase 
								letter, one number and one special character'
							)
						],
						'label' => 'New password',
					],
					'second_options' => [
						'label' => 'Repeat Password',
					],
					'invalid_message' => 'The password fields must match.',
					// Instead of being set onto the object directly,
					// this is read and encoded in the controller
					'mapped' => false,
				]
			);
	}

	/**
	 * @inheritDoc
	 */
	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([]);
	}
}
