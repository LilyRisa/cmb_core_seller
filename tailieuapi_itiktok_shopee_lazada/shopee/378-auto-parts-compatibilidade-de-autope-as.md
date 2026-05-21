# AUTO PARTS: COMPATIBILIDADE DE AUTOPEÇAS

> Source: https://open.shopee.com/developer-guide/378
> Category: 
> Scraped: 2026-05-20T20:38:24.605Z

---

A partir do dia 24 de junho de 2024 está disponível na Shopee a feature que permite adicionar compatibilidade entre veículos e produtos de auto peças. Com isso alguns endpoints foram criados e outros tiveram atualizações relacionadas a esse tema.

### Vantagens no uso da feature de compatibilidade

  

-   Sellers poderão configurar itens mais completos, com os dados de veículos compatíveis com seus produtos;
-   Os itens com compatibilidade preenchida irão aparecer em posições de melhor relevância na página de resultados da pesquisa (SRP) do buyer;
-   O buyer terá maior confiabilidade na compra, uma vez que será mais fácil confirmar se um item é de fato compatível com seu veículo.

  

Considerando esse cenário, se o seus clientes (os sellers) trabalham com itens de autopeças, recomendamos fortemente que implementem os novos endpoints na plataforma de vocês.

### Estrutura dos dados

  

Os dados dos veículos são armazenados da seguinte forma:

| Parâmetro | Significado | Exemplo |
| --- | --- | --- |
| brand\_id | O id de uma marca específica. | 5770 |
| brand\_name | O nome da marca relacionada a cada brand\_id. | "Chevrolet" |
| model\_id | O id de um modelo específico. | 5905 |
| model\_name | O nome do modelo relacionado ao model\_id. | "Chevette" |
| year\_id | O id de cada ano. | 5712 |
| year\_name | O ano relacionado a um dado year\_id. | "1979" |
| version\_id | A id de cada versão de um veículo. | 5907 |
| version\_name | O nome da versão associada ao version\_id. | "Hatch" |

  

Estabelecido isso, é importante reforçar que os parâmetros listados acima seguem a seguinte relação de dependência: brand > model > year > version.

### Novos endpoints:

  

v2.product.get\_all\_vehicle\_list

  

-   Como o nome indica, é o endpoint responsável por trazer toda a lista de veículos presente na base de dados da Shopee. Para usá-lo, o parâmetro obrigatório é o tamanho da página (page\_size) que pode ser de no máximo 100 itens. Mais detalhes do endpoint você encontra em [sua página do API Reference](https://open.shopee.com/documents/v2/v2.product.get_all_vehicle_list?module=89&type=1).
-   Exemplo de retorno do endpoint:

Python

```
{
    "error": "",
    "message": "",
    "warning": "",
    "request_id": "6535ecb33a3f8c6900c8d2b515f3c821",
    "response": {
        "vehicle_list": [
            {
                "brand_id": 5770,
                "brand_name": "Chevrolet",
                "model_id": 5905,
                "model_name": "Chevette",
                "year_id": 5712,
                "year_name": "1979",
                "version_id": 5907,
                "version_name": "Hatch"
            },
            {
                "brand_id": 5770,
                "brand_name": "Chevrolet",
                "model_id": 5905,
                "model_name": "Chevette",
                "year_id": 5712,
                "year_name": "1979",
                "version_id": 5906,
                "version_name": "S"
            }....
           ],
        "has_next_page": true,
        "next_offset": 61
    }
}
```

  

v2.product.get\_vehicle\_list\_by\_compatibility\_detail

  

-   Nesse endpoint é possível identificar os detalhes para cada um dos elementos do veículo (marca / modelo / ano / versão). O parâmetro obrigatório é o "compatibility\_details" onde você pode especificar que nível de detalhamento deseja ver. Parâmetros opcionais (brand\_id / model\_id / year\_id / version\_id) ajudam no refinamento da busca. Mais informações em sua [página na API Reference](https://open.shopee.com/documents/v2/v2.product.get_vehicle_list_by_compatibility_detail?module=89&type=1).
-   Exemplos de chamadas no endpoint:

| Request | Response |
| --- | --- |
| compatibility\_details\="Brand" | 
Python

```

"response": {
                "compatibility_tree": [
                    {
                       "compatibility_details": [
                            {
                                 "brand_id": 12345,
                                 "brand_name": "Toyota",
                            },
                            {
                                 "brand_id": 13524,
                                 "brand_name": "Renault",
                            },
                            {
                                "brand_id": 14235,
                                "brand_name": "Chevrolet",
                            },
 …
```

 |
| compatibility\_brand\_id\=12345&compatibility\_details\="Model" | 

Python

```

"response": {
               "compatibility_tree": [
                   {
                  "compatibility_details": [
                            {
                                 "model_id": 222234,
                               "model_name": "Etios",
                            },
                            {
                                "model_id": 234234,
                                 "model_name": "Corolla",
                            },
                            {
                                 "model_id": 225243,
                                 "model_name": "Bandeirante",
                           },
 …
```

 |

### Mudanças em endpoints existentes

  

Alguns endpoints sofreram ajustes para que possam se adequar ao fluxo de compatibilidade, são eles:

-   V2.product.add\_item;
-   V2.product.update\_item;
-   v2.product.get\_item\_base\_info;

  

Nos dois primeiros endpoints é possível inserir as informações de compatibilidade para itens novos / existentes, respectivamente. No terceiro as mudanças auxiliam a confirmar se as informações de compatibilidade foram adicionadas corretamente.

  

Exemplo de estrutura para chamadas no v2.product.add\_item e v2.product.update\_item

Python

```
    "compatibility_info": {
        "vehicle_info_list": [
            {
                "brand_id": 5770,
                "model_id": 5911,
                "year_id": 5590,
                "version_id": 5912
            },
            {
                "brand_id": 5508,
                "model_id": 5509,
                "year_id": 5516
            },
            {
                "brand_id": 5770,
                "model_id": 5905
            }
        ]
    },
```

  

Algumas considerações importantes:

-   Se um determinado produto é compatível com todas as versões de um determinado ano, basta informar os ids para brand, model e year, o sistema entenderá que todas as versões daquele ano deverão ser inseridas na lista de compatibilidade;

Python

```
			{
                "brand_id": 5508,
                "model_id": 5509,
                "year_id": 5516
            }
```

  

-   De maneira similar, se todas as versões de todos os anos de um dado modelo forem compatíveis com o produto, basta informar os ids para brand e model;

Python

```
            {
                "brand_id": 5770,
                "model_id": 5905
            }
```

  

-   O v2.product.update\_item poderá ser usado normalmente para adicionar as informações de compatibilidade em itens existentes que não a possuam. Nesse caso, basta informar a lista de compatibilidade conforme exemplos acima;

  

-   ATENÇÃO: Ao usar o v2.product.update\_item para adicionar compatibilidade em um item que já possui veículos em sua lista, você deverá informar a lista de ids existente atualmente no item + os novos ids de compatibilidade. Caso contrário, a lista de ids existente será sobreposta pelos novos ids informados na chamada do update item.

  

Se restar alguma dúvida no uso dos novos endpoints, [basta abrir um ticket](https://open.shopee.com/myconsole/ticket-system/raise-ticket/) e iremos te auxiliar a integrar corretamente com a nova funcionalidade.
