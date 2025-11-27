import json
import os

def calcular_comissao(valor_venda):
    """
    Calcula a comissão baseada no valor da venda
    
    Regras:
    - Vendas abaixo de R$100,00 não gera comissão
    - Vendas abaixo de R$500,00 gera 1% de comissão
    - A partir de R$500,00 gera 5% de comissão
    """
    if valor_venda < 100:
        return 0
    elif valor_venda < 500:
        return valor_venda * 0.01
    else:
        return valor_venda * 0.05

def carregar_dados(caminho_arquivo):
    """Carrega os dados do arquivo JSON"""
    try:
        with open(caminho_arquivo, 'r', encoding='utf-8') as file:
            return json.load(file)
    except FileNotFoundError:
        print(f"Erro: Arquivo {caminho_arquivo} não encontrado!")
        return None
    except json.JSONDecodeError:
        print("Erro: Arquivo JSON inválido!")
        return None

def calcular_comissoes_vendedores(dados):
    """Calcula a comissão total para cada vendedor"""
    comissoes = {}
    
    for venda in dados["vendas"]:
        vendedor = venda["vendedor"]
        valor = venda["valor"]
        
        comissao_venda = calcular_comissao(valor)
        
        if vendedor not in comissoes:
            comissoes[vendedor] = 0
        
        comissoes[vendedor] += comissao_venda
    
    return comissoes

def exibir_relatorio_detalhado(dados):
    """Exibe um relatório detalhado com todas as vendas e comissões"""
    print("=" * 60)
    print("RELATÓRIO DETALHADO DE COMISSÕES")
    print("=" * 60)
    
    # Agrupar vendas por vendedor
    vendas_por_vendedor = {}
    for venda in dados["vendas"]:
        vendedor = venda["vendedor"]
        if vendedor not in vendas_por_vendedor:
            vendas_por_vendedor[vendedor] = []
        vendas_por_vendedor[vendedor].append(venda["valor"])
    
    # Calcular e exibir para cada vendedor
    for vendedor, vendas in vendas_por_vendedor.items():
        print(f"\nVENDEDOR: {vendedor}")
        print("-" * 40)
        
        total_vendas = 0
        total_comissao = 0
        
        for i, valor in enumerate(vendas, 1):
            comissao = calcular_comissao(valor)
            total_vendas += valor
            total_comissao += comissao
            
            print(f"Venda {i:2d}: R$ {valor:8.2f} | Comissão: R$ {comissao:6.2f}")
        
        print("-" * 40)
        print(f"TOTAL VENDAS: R$ {total_vendas:8.2f}")
        print(f"TOTAL COMISSÃO: R$ {total_comissao:8.2f}")

def main():
    """Função principal do programa"""
    caminho_arquivo = os.path.join('data', 'vendas.json')
    dados = carregar_dados(caminho_arquivo)
    
    if dados:
        # Exibir relatório detalhado
        exibir_relatorio_detalhado(dados)
        
        # Calcular e exibir resumo
        comissoes_totais = calcular_comissoes_vendedores(dados)
        
        print("\n" + "=" * 60)
        print("RESUMO DAS COMISSÕES TOTAIS")
        print("=" * 60)
        
        for vendedor, comissao in comissoes_totais.items():
            print(f"{vendedor}: R$ {comissao:.2f}")

if __name__ == "__main__":
    main()
