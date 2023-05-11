<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{PasswordType, RepeatedType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\{Length, NotBlank};

/**
 * defines the form used to change the user password
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
							'autocomplete' => 'new-password',
						],
					],
					'first_options' => [
						'constraints' => [
							new NotBlank(
								[
									'message' => 'Please enter a password',
								]
							),
							new Length(
								[
									'min' => 6,
									'minMessage' => 'Your password should be at least {{ limit }} characters',
									// max length allowed by Symfony for security reasons
									'max' => 4096,
								]
							),
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
