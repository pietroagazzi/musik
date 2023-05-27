<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, EmailType, PasswordType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\{IsTrue, Length, NotBlank, Regex};

/**
 * defines the form used to create and manipulate user accounts
 */
class RegistrationFormType extends AbstractType
{
	/**
	 * @inheritDoc
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add(
				'email', EmailType::class, [
					'label' => 'Email',
					'attr' => [
						'placeholder' => 'Email',
					],
				]
			)
			->add(
				'username', TextType::class, [
					'label' => 'Username',
					'attr' => [
						'placeholder' => 'Username',
					],
				]
			)
			->add(
				'agreeTerms', CheckboxType::class, [
					'mapped' => false,
					'constraints' => [
						new IsTrue(
							[
								'message' => 'You should agree to our terms.',
							]
						),
					],
				]
			)
			->add(
				'plainPassword', PasswordType::class, [
					'label' => 'Password',
					// instead of being set onto the object directly,
					// this is read and encoded in the controller
					'mapped' => false,
					'attr' => [
						'autocomplete' => 'new-password',
						'placeholder' => 'Password'
					],
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
								'max' => 50,
								'maxMessage' => 'Your password should be at most {{ limit }} characters',
							]
						),
					],
				]
			);
	}

	/**
	 * @inheritDoc
	 */
	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(
			[
				'data_class' => User::class,
			]
		);
	}
}
