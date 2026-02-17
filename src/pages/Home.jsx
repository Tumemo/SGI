import { Link } from "react-router-dom"
import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import Botao from "../components/Botao/Botao"
import NavBar from "../components/NavBar/NavBar"
import ButtonCard from "../components/ButtonCard/ButtonCard"

function Home() {
    return (
        < >
            <BannerGlobal />
            <Link to={"/NovaEdicao"}>
                <Botao> <img src="/icons/plus-icon.svg" alt="Mais" /> Criar Nova edição</Botao>
            </Link>
            <section className="w-full py-10 flex flex-col justify-center a">
                <ButtonCard estrutura="complexo" nome="Gustavo" modalidade="Basquete"  />
                <ButtonCard estrutura="complexo" nome="Gustavo" modalidade="Basquete" tipoComplexo="pontos"  />
                <ButtonCard texto="Interclasse 2026" status="Em andamento"/>
                <ButtonCard texto="Interclasse 2025" status="Finalizado" />
                <ButtonCard texto="Interclasse 2024" status="Finalizado" />
                <ButtonCard texto="Interclasse - Volta as Aulas" status="Finalizado" />
            </section>
            <NavBar />
        </>
    )
}

export default Home