<?php
/**
 * Explainable Update Risk Assessment Engine.
 *
 * @package MaxSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxSafe_Risk_Assessor {

	// Security Urgency Labels
	const URGENCY_ROUTINE   = 'Routine';
	const URGENCY_IMPORTANT = 'Important';
	const URGENCY_CRITICAL  = 'Critical';

	// Deployment Confidence Labels
	const CONFIDENCE_LOW      = 'Low';
	const CONFIDENCE_MODERATE = 'Moderate';
	const CONFIDENCE_HIGH     = 'High';
	const CONFIDENCE_UNKNOWN  = 'Unknown';

	/**
	 * Assess a single update candidate component.
	 *
	 * @param array $component Component inventory item (core, plugin, or theme).
	 * @param array $context   System context (backups, staging tests, PHP version, WooCommerce active).
	 * @return array Explainable risk profile.
	 */
	public static function assess_component( $component, $context = array() ) {
		$reasons          = array();
		$evidence_sources = array();
		$urgency          = self::URGENCY_ROUTINE;
		$confidence       = self::CONFIDENCE_UNKNOWN;

		$installed_ver = isset( $component['installed_version'] ) ? $component['installed_version'] : '0.0.0';
		$new_ver       = isset( $component['new_version'] ) ? $component['new_version'] : '0.0.0';
		$is_active     = ! empty( $component['is_active'] );
		$slug          = isset( $component['slug'] ) ? $component['slug'] : 'component';

		// 1. Analyze SemVer Version Jump (Major vs Minor vs Patch)
		$version_diff = self::get_version_bump_type( $installed_ver, $new_ver );
		if ( 'major' === $version_diff ) {
			$reasons[] = sprintf(
				/* translators: 1: Old version, 2: New version */
				__( 'Major version upgrade detected (%1$s -> %2$s), which often introduces breaking architectural API changes.', 'maxsafe' ),
				$installed_ver,
				$new_ver
			);
			$evidence_sources[] = __( 'Semantic Versioning Analysis', 'maxsafe' );
		} elseif ( 'minor' === $version_diff ) {
			$reasons[] = sprintf(
				/* translators: 1: Old version, 2: New version */
				__( 'Minor feature release (%1$s -> %2$s). New functionality introduced.', 'maxsafe' ),
				$installed_ver,
				$new_ver
			);
			$evidence_sources[] = __( 'Semantic Versioning Analysis', 'maxsafe' );
		}

		// 2. Vulnerability & Security Urgency Check
		$known_cves = isset( $context['vulnerabilities'][ $slug ] ) ? $context['vulnerabilities'][ $slug ] : array();
		if ( ! empty( $known_cves ) ) {
			$has_critical_cve = false;
			foreach ( $known_cves as $cve ) {
				if ( in_array( strtolower( $cve['severity'] ), array( 'critical', 'high' ), true ) ) {
					$has_critical_cve = true;
					break;
				}
			}

			if ( $has_critical_cve ) {
				$urgency   = self::URGENCY_CRITICAL;
				$reasons[] = sprintf(
					/* translators: %d: Number of security advisories */
					__( 'ACTIVE SECURITY VULNERABILITY: Installed version has %d reported CVE advisories requiring remediation.', 'maxsafe' ),
					count( $known_cves )
				);
			} else {
				$urgency   = self::URGENCY_IMPORTANT;
				$reasons[] = sprintf(
					/* translators: %d: Number of security advisories */
					__( 'Security advisory exists for installed version (%d advisories). Update recommended.', 'maxsafe' ),
					count( $known_cves )
				);
			}
			$evidence_sources[] = __( 'Vulnerability Intelligence Feed', 'maxsafe' );
		}

		// 3. PHP Runtime Compatibility Check
		$requires_php = isset( $component['requires_php'] ) ? $component['requires_php'] : '';
		if ( ! empty( $requires_php ) ) {
			if ( version_compare( PHP_VERSION, $requires_php, '<' ) ) {
				$reasons[] = sprintf(
					/* translators: 1: Component requirement, 2: Server PHP version */
					__( 'INCOMPATIBLE: Requires PHP %1$s but server runtime is %2$s. Update will trigger fatal error.', 'maxsafe' ),
					$requires_php,
					PHP_VERSION
				);
				$confidence = self::CONFIDENCE_LOW;
				$evidence_sources[] = __( 'Component Metadata: RequiresPHP', 'maxsafe' );
			} else {
				$reasons[] = sprintf(
					/* translators: 1: Component requirement, 2: Server PHP version */
					__( 'PHP compatibility verified: Component declared PHP %1$s+ requirement matches runtime %2$s.', 'maxsafe' ),
					$requires_php,
					PHP_VERSION
				);
				$evidence_sources[] = __( 'Component Metadata: RequiresPHP', 'maxsafe' );
			}
		}

		// 4. Critical Business Path: WooCommerce check
		$has_woocommerce = ! empty( $context['has_woocommerce'] );
		if ( $has_woocommerce && ( 'woocommerce' === $slug || $is_active ) ) {
			$reasons[] = __( 'Critical e-commerce path detected. Checkout funnel must be validated in staging prior to production deploy.', 'maxsafe' );
			$evidence_sources[] = __( 'Site Inventory: WooCommerce Active', 'maxsafe' );
		}

		// 5. Backup Verification Status
		$backup_freshness_hours = isset( $context['recent_verified_backup_hours'] ) ? $context['recent_verified_backup_hours'] : null;
		if ( null === $backup_freshness_hours || $backup_freshness_hours > 24 ) {
			$reasons[] = __( 'No verified backup archive found within the last 24 hours. A fresh, restorable backup must be taken first.', 'maxsafe' );
			$evidence_sources[] = __( 'MaxSafe Backup Adapter', 'maxsafe' );
		} else {
			$reasons[] = sprintf(
				/* translators: %d: Hours since last backup */
				__( 'Verified restorable backup exists (%d hours old).', 'maxsafe' ),
				$backup_freshness_hours
			);
			$evidence_sources[] = __( 'MaxSafe Backup Adapter', 'maxsafe' );
		}

		// 6. Staging & Visual Test Status
		$staging_tested = ! empty( $context['staging_tests_passed'] );
		$staging_failed = ! empty( $context['staging_tests_failed'] );

		if ( $staging_failed ) {
			$confidence = self::CONFIDENCE_LOW;
			$reasons[]  = __( 'STAGING REGRESSION DETECTED: Automated smoke tests or visual regression failed on staging snapshot.', 'maxsafe' );
			$evidence_sources[] = __( 'MaxSafe Staging Test Runner', 'maxsafe' );
		} elseif ( $staging_tested ) {
			$confidence = self::CONFIDENCE_HIGH;
			$reasons[]  = __( 'Staging smoke tests passed and visual regression thresholds validated successfully.', 'maxsafe' );
			$evidence_sources[] = __( 'MaxSafe Staging Test Runner', 'maxsafe' );
		} else {
			// No staging test executed yet
			if ( self::CONFIDENCE_LOW !== $confidence ) {
				$confidence = ( 'major' === $version_diff ) ? self::CONFIDENCE_LOW : self::CONFIDENCE_MODERATE;
			}
			$reasons[] = __( 'Staging validation has not yet run for this candidate update version.', 'maxsafe' );
		}

		// Determine Next Recommended Step
		$next_action = self::determine_next_action( $urgency, $confidence, $backup_freshness_hours, $staging_tested );

		return array(
			'component_slug'    => $slug,
			'installed_version' => $installed_ver,
			'new_version'       => $new_ver,
			'version_bump'      => $version_diff,
			'security_urgency'  => $urgency,
			'deployment_confidence' => $confidence,
			'overall_risk'      => self::derive_overall_risk( $urgency, $confidence ),
			'reasons'           => $reasons,
			'evidence_sources'  => array_unique( $evidence_sources ),
			'next_action'       => $next_action,
			'assessed_at'       => current_time( 'mysql' ),
		);
	}

	/**
	 * Compute overall risk label for human scanning.
	 *
	 * @param string $urgency
	 * @param string $confidence
	 * @return string
	 */
	public static function derive_overall_risk( $urgency, $confidence ) {
		if ( self::CONFIDENCE_LOW === $confidence ) {
			return 'High Risk';
		}
		if ( self::URGENCY_CRITICAL === $urgency ) {
			return ( self::CONFIDENCE_HIGH === $confidence ) ? 'Moderate Risk (Urgent Patch)' : 'High Risk (Urgent Patch)';
		}
		if ( self::CONFIDENCE_HIGH === $confidence ) {
			return 'Low Risk';
		}
		if ( self::CONFIDENCE_MODERATE === $confidence ) {
			return 'Moderate Risk';
		}
		return 'Unknown Risk';
	}

	/**
	 * Determine next action based on explainable factors.
	 */
	private static function determine_next_action( $urgency, $confidence, $backup_hours, $staging_tested ) {
		if ( null === $backup_hours || $backup_hours > 24 ) {
			return __( 'Create and verify fresh full-site backup archive.', 'maxsafe' );
		}
		if ( ! $staging_tested ) {
			return __( 'Launch Staging Clone and run automated smoke & visual regression tests.', 'maxsafe' );
		}
		if ( self::CONFIDENCE_LOW === $confidence ) {
			return __( 'Review staging failure logs and consult plugin changelog before proceeding.', 'maxsafe' );
		}
		return __( 'Ready for administrator approval and controlled production deployment.', 'maxsafe' );
	}

	/**
	 * Determine Semantic Versioning jump type.
	 */
	public static function get_version_bump_type( $old_ver, $new_ver ) {
		$old_parts = explode( '.', preg_replace( '/[^0-9.]/', '', $old_ver ) );
		$new_parts = explode( '.', preg_replace( '/[^0-9.]/', '', $new_ver ) );

		$old_major = isset( $old_parts[0] ) ? (int) $old_parts[0] : 0;
		$new_major = isset( $new_parts[0] ) ? (int) $new_parts[0] : 0;

		$old_minor = isset( $old_parts[1] ) ? (int) $old_parts[1] : 0;
		$new_minor = isset( $new_parts[1] ) ? (int) $new_parts[1] : 0;

		if ( $new_major > $old_major ) {
			return 'major';
		}
		if ( $new_minor > $old_minor ) {
			return 'minor';
		}
		return 'patch';
	}
}
