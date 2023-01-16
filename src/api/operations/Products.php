<?php

namespace venveo\bigcommerce\api\operations;

use JetBrains\PhpStorm\ArrayShape;
use venveo\bigcommerce\base\ApiOperationInterface;
use venveo\bigcommerce\helpers\ApiHelper;

class Products implements ApiOperationInterface
{
    /**
     * Gets product information from BigCommerce. Note, this will return any customer-specific pricing if the customer is logged in
     * @param $productId
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function getProductInformationById($productId, #[ArrayShape([[
        'optionEntityId' => 'int',
        'valueEntityId' => 'int'
    ]])] array|null $options = []): \Psr\Http\Message\ResponseInterface
    {
        $options = $options ?? [];
        $optionsParam = $options ? '$optionValueIds: [OptionValueId!]' : '';
        $optionsQuery = $options ? 'optionValueIds: $optionValueIds' : '';
        $query = sprintf('
query productById(
  $productId: Int!
  %s
)
{
  site {
    product(
        entityId: $productId
        %s
    ) {
      id
      entityId
      name
      sku
      availabilityV2 {
        status
        description
      }
      minPurchaseQuantity
      maxPurchaseQuantity
      variants(first: 25) {
        edges {
          node {
            isPurchasable
            inventory {
                isInStock            
            }
            prices {
              price {
                ...MoneyFields
              }
              priceRange {
                min {
                  ...MoneyFields
                }
                max {
                  ...MoneyFields
                }
              }
              salePrice {
                ...MoneyFields
              }
              retailPrice {
                ...MoneyFields
              }
            }
            sku
            id
            entityId
          }
        }
      }
      productOptions(first: 50) {
        edges {
        ...OptionFields
        }
      }
      inventory {
        hasVariantInventory
        isInStock
      }
      prices {
        price {
          ...MoneyFields
        }
        priceRange {
          min {
            ...MoneyFields
          }
          max {
            ...MoneyFields
          }
        }
        salePrice {
          ...MoneyFields
        }
        retailPrice {
          ...MoneyFields
        }
        saved {
          ...MoneyFields
        }
        bulkPricing {
          minimumQuantity
          maximumQuantity
          ... on BulkPricingFixedPriceDiscount {
            price
          }
          ... on BulkPricingPercentageDiscount {
            percentOff
          }
          ... on BulkPricingRelativePriceDiscount {
            priceAdjustment
          }
        }
      }
      brand {
        name
      }
    }
  }
}

fragment OptionFields on ProductOptionEdge {
            node {
            __typename
            entityId
            displayName
            isRequired
            isVariantOption
            ... on CheckboxOption {
              checkedByDefault
              label
            }
            ... on TextFieldOption {
              textDefaultValue: defaultValue
              minLength
              maxLength
            }
            ... on NumberFieldOption {
              numberDefaultValue: defaultValue
              lowest
              highest
              isIntegerOnly
              limitNumberBy
            }
            ... on DateFieldOption {
              dateDefaultValue: defaultValue
              earliest
              latest
              limitDateBy
            }
            ... on MultiLineTextFieldOption {
              textDefaultValue: defaultValue
              minLength
              maxLength
              maxLines
            }
            ... on FileUploadFieldOption {
              maxFileSize
              fileTypes
            }
            ... on MultipleChoiceOption {
              displayStyle
              values(first: 10) {
                edges {
                  node {
                    entityId
                    label
                    isDefault
                    ... on SwatchOptionValue {
                      hexColors
                      imageUrl(width: 200)
                    }
                  }
                }
              }
            }
          }
        }

fragment MoneyFields on Money {
  value
  currencyCode
}
', $optionsParam, $optionsQuery);
        $variables = ['productId' => $productId];
        if ($options) {
            $variables['optionValueIds'] = $options;
        }
        $response = ApiHelper::sendGraphQLRequest($query, $variables, true);
        return $response;
    }
}